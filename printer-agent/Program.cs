using System.Drawing;
using System.Drawing.Printing;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
using Microsoft.Win32;

namespace CardapioPrinterAgent;

internal static class Program
{
    [STAThread]
    private static void Main()
    {
        using var mutex = new Mutex(true, @"Local\CardapioDigitalBH.PrintAgent", out var createdNew);
        if (!createdNew) return;

        ApplicationConfiguration.Initialize();
        Application.Run(new AgentContext());
    }
}

internal sealed class AgentContext : ApplicationContext
{
    private static readonly HttpClient Client = new() { Timeout = TimeSpan.FromSeconds(12) };
    private readonly Icon _agentIcon;
    private readonly NotifyIcon _icon;
    private readonly ToolStripMenuItem _status;
    private readonly ToolStripMenuItem _printerMenu;
    private readonly Control _dispatcher = new();
    private readonly string _directory = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "CardapioDigitalBH");
    private readonly string _configPath;
    private readonly string _printedPath;
    private AgentConfig? _config;
    private HashSet<long> _printed = [];
    private int _polling;
    private bool _exiting;

    public AgentContext()
    {
        _dispatcher.CreateControl();
        _ = _dispatcher.Handle;
        Directory.CreateDirectory(_directory);
        _configPath = Path.Combine(_directory, "agent.json");
        _printedPath = Path.Combine(_directory, "printed-jobs.json");
        _config = LoadConfig();
        _printed = LoadPrinted();

        _status = new ToolStripMenuItem("Agente iniciado") { Enabled = false };
        _printerMenu = new ToolStripMenuItem("Selecionar impressora");
        var startAtLogin = new ToolStripMenuItem("Iniciar com o Windows") { CheckOnClick = true };
        startAtLogin.Checked = IsAutoStartEnabled();
        startAtLogin.CheckedChanged += (_, _) => SetAutoStart(startAtLogin.Checked);
        var menu = new ContextMenuStrip();
        menu.Items.Add(_status);
        menu.Items.Add(_printerMenu);
        menu.Items.Add(startAtLogin);
        menu.Items.Add(new ToolStripSeparator());
        menu.Items.Add("Sair", null, (_, _) => ExitThread());

        _agentIcon = Icon.ExtractAssociatedIcon(Application.ExecutablePath) ?? (Icon)SystemIcons.Application.Clone();
        _icon = new NotifyIcon { Icon = _agentIcon, Text = "Cardápio Digital BH · Impressão", ContextMenuStrip = menu, Visible = true };
        _icon.DoubleClick += (_, _) => ShowStatus();
        RefreshPrinterMenu();

        if (_config is null)
        {
            BeginPairing();
        }
        else
        {
            SetStatus(_config.PrinterName is null ? "Escolha uma impressora" : "Conectando à fila de pedidos");
            StartPolling();
        }
    }

    protected override void ExitThreadCore()
    {
        _exiting = true;
        _config = null;
        _icon.Visible = false;
        _icon.Dispose();
        _agentIcon.Dispose();
        _dispatcher.Dispose();
        base.ExitThreadCore();
    }

    private void BeginPairing()
    {
        using var dialog = new PairingForm();
        if (dialog.ShowDialog() != DialogResult.OK) return;
        PairAsync(dialog.BaseUrl, dialog.Code, dialog.DeviceName);
    }

    private async void PairAsync(string baseUrl, string code, string deviceName)
    {
        try
        {
            var root = NormalizeBaseUrl(baseUrl);
            var response = await Client.PostAsJsonAsync(root + "/api/print-agent/pair", new { code, name = deviceName });
            if (!response.IsSuccessStatusCode) throw new InvalidOperationException("Código inválido ou expirado. Gere outro no painel.");
            using var body = await JsonDocument.ParseAsync(await response.Content.ReadAsStreamAsync());
            var token = body.RootElement.GetProperty("token").GetString() ?? throw new InvalidOperationException("Resposta de pareamento inválida.");
            var protectedToken = ProtectedData.Protect(Encoding.UTF8.GetBytes(token), Entropy(), DataProtectionScope.CurrentUser);
            _config = new AgentConfig(root, Convert.ToBase64String(protectedToken), deviceName, null);
            SaveConfig();
            RefreshPrinterMenu();
            SetStatus("Escolha uma impressora");
            StartPolling();
        }
        catch (Exception error)
        {
            MessageBox.Show(error.Message, "Cardápio Digital BH", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            BeginPairing();
        }
    }

    private void StartPolling() => _ = Task.Run(async () =>
    {
        while (_config is not null)
        {
            await PollOnce();
            await Task.Delay(TimeSpan.FromSeconds(4));
        }
    });

    private async Task PollOnce()
    {
        var config = _config;
        if (config is null || string.IsNullOrWhiteSpace(config.PrinterName) || Interlocked.Exchange(ref _polling, 1) == 1) return;
        try
        {
            var token = Encoding.UTF8.GetString(ProtectedData.Unprotect(Convert.FromBase64String(config.ProtectedToken), Entropy(), DataProtectionScope.CurrentUser));
            using var request = new HttpRequestMessage(HttpMethod.Get, config.BaseUrl + "/api/print-agent/jobs");
            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", token);
            using var response = await Client.SendAsync(request);
            if (!response.IsSuccessStatusCode) throw new InvalidOperationException("Conexão recusada. Confira o pareamento no painel.");
            var data = await response.Content.ReadFromJsonAsync<JobResponse>();
            foreach (var job in data?.Jobs ?? []) await ProcessJob(config, token, job);
            SetStatus("Conectado · aguardando pedidos");
        }
        catch (Exception)
        {
            SetStatus("Sem conexão · tentando novamente");
        }
        finally
        {
            Interlocked.Exchange(ref _polling, 0);
        }
    }

    private async Task ProcessJob(AgentConfig config, string token, PrintJobDto job)
    {
        if (_printed.Contains(job.Id))
        {
            await Acknowledge(config, token, job.Id, true, null);
            return;
        }

        try
        {
            PrintTicket(config.PrinterName!, job.Payload);
            _printed.Add(job.Id);
            if (_printed.Count > 500) _printed = _printed.OrderByDescending(id => id).Take(500).ToHashSet();
            SavePrinted();
            await Acknowledge(config, token, job.Id, true, null);
            SetStatus($"Pedido #{job.Payload.OrderId} impresso");
        }
        catch (Exception)
        {
            await Acknowledge(config, token, job.Id, false, "Verifique a conexão, o papel e a fila da impressora no Windows.");
            SetStatus("Falha ao imprimir · confira a impressora");
        }
    }

    private static async Task Acknowledge(AgentConfig config, string token, long jobId, bool success, string? error)
    {
        using var request = new HttpRequestMessage(HttpMethod.Post, $"{config.BaseUrl}/api/print-agent/jobs/{jobId}/ack");
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", token);
        request.Content = JsonContent.Create(new { success, printer_name = config.PrinterName, error });
        using var response = await Client.SendAsync(request);
        response.EnsureSuccessStatusCode();
    }

    private static void PrintTicket(string printerName, TicketPayload ticket)
    {
        using var document = new PrintDocument();
        document.PrinterSettings.PrinterName = printerName;
        if (!document.PrinterSettings.IsValid) throw new InvalidOperationException("A impressora selecionada não está disponível no Windows.");
        document.PrintController = new StandardPrintController();
        document.DocumentName = $"Cardápio Digital BH · Pedido #{ticket.OrderId}";
        document.DefaultPageSettings.Margins = new Margins(12, 12, 12, 12);

        var lines = BuildLines(ticket);
        var index = 0;
        document.PrintPage += (_, page) =>
        {
            using var font = new Font("Consolas", 9, FontStyle.Regular);
            using var bold = new Font("Consolas", 10, FontStyle.Bold);
            using var brush = new SolidBrush(Color.Black);
            float y = page.MarginBounds.Top;
            var width = page.MarginBounds.Width;
            foreach (var (text, emphasize) in lines.Skip(index))
            {
                var activeFont = emphasize ? bold : font;
                var size = page.Graphics!.MeasureString(text, activeFont, width);
                if (y + size.Height > page.MarginBounds.Bottom)
                {
                    page.HasMorePages = true;
                    return;
                }
                page.Graphics.DrawString(text, activeFont, brush, new RectangleF(page.MarginBounds.Left, y, width, size.Height + 2));
                y += size.Height + 2;
                index++;
            }
            page.HasMorePages = false;
        };
        document.Print();
    }

    private static List<(string Text, bool Emphasize)> BuildLines(TicketPayload ticket)
    {
        var lines = new List<(string, bool)> { (ticket.VenueName.ToUpperInvariant(), true), ($"PEDIDO #{ticket.OrderId}", true), ($"{ticket.CreatedAt} · {ticket.Channel}", false), (new string('-', 30), false), (ticket.CustomerName, true) };
        foreach (var item in ticket.Items)
        {
            lines.Add(($"{item.Quantity}x {item.Name}", true));
            lines.AddRange(item.Options.Select(option => ($"  + {option}", false)));
            if (!string.IsNullOrWhiteSpace(item.Notes)) lines.Add(($"  Obs: {item.Notes}", false));
        }
        if (!string.IsNullOrWhiteSpace(ticket.Notes)) lines.Add(($"Observação: {ticket.Notes}", false));
        lines.Add((new string('-', 30), false));
        lines.Add(($"TOTAL R$ {ticket.Total}", true));
        lines.Add(("", false));
        return lines;
    }

    private void RefreshPrinterMenu()
    {
        _printerMenu.DropDownItems.Clear();
        foreach (string printer in PrinterSettings.InstalledPrinters)
        {
            var item = new ToolStripMenuItem(printer) { Checked = _config?.PrinterName == printer, CheckOnClick = false };
            item.Click += (_, _) =>
            {
                if (_config is null) return;
                _config = _config with { PrinterName = printer };
                SaveConfig();
                RefreshPrinterMenu();
                SetStatus("Impressora selecionada · aguardando pedidos");
            };
            _printerMenu.DropDownItems.Add(item);
        }
        if (_printerMenu.DropDownItems.Count == 0) _printerMenu.DropDownItems.Add(new ToolStripMenuItem("Nenhuma impressora do Windows encontrada") { Enabled = false });
    }

    private void SetStatus(string value)
    {
        if (_exiting) return;
        _dispatcher.BeginInvoke(new Action(() => { _status.Text = value; _icon.Text = value.Length > 63 ? value[..63] : value; }));
    }

    private void ShowStatus() => MessageBox.Show(_status.Text, "Cardápio Digital BH · Impressão", MessageBoxButtons.OK, MessageBoxIcon.Information);

    private AgentConfig? LoadConfig()
    {
        if (!File.Exists(_configPath)) return null;
        try { return JsonSerializer.Deserialize<AgentConfig>(File.ReadAllText(_configPath)); }
        catch { return null; }
    }

    private void SaveConfig() => WriteAtomically(_configPath, JsonSerializer.Serialize(_config));

    private HashSet<long> LoadPrinted()
    {
        try { return File.Exists(_printedPath) ? JsonSerializer.Deserialize<HashSet<long>>(File.ReadAllText(_printedPath)) ?? [] : []; }
        catch { return []; }
    }

    private void SavePrinted() => WriteAtomically(_printedPath, JsonSerializer.Serialize(_printed));

    private static void WriteAtomically(string path, string contents)
    {
        var temporaryPath = Path.Combine(
            Path.GetDirectoryName(path)!,
            $".{Path.GetFileName(path)}.{Guid.NewGuid():N}.tmp");

        try
        {
            var bytes = new UTF8Encoding(false).GetBytes(contents);
            using (var stream = new FileStream(temporaryPath, FileMode.CreateNew, FileAccess.Write, FileShare.None))
            {
                stream.Write(bytes, 0, bytes.Length);
                stream.Flush(flushToDisk: true);
            }

            File.Move(temporaryPath, path, overwrite: true);
        }
        finally
        {
            try { File.Delete(temporaryPath); }
            catch { }
        }
    }

    private static byte[] Entropy() => Encoding.UTF8.GetBytes("CardapioDigitalBH.PrintAgent.v1");

    private static string NormalizeBaseUrl(string value)
    {
        if (!Uri.TryCreate(value.Trim(), UriKind.Absolute, out var uri) || uri.Scheme != Uri.UriSchemeHttps)
            throw new InvalidOperationException("Use o endereço HTTPS do sistema, por exemplo https://cardapiodigitalbh.com.br.");
        return uri.GetLeftPart(UriPartial.Authority).TrimEnd('/');
    }

    private bool IsAutoStartEnabled()
    {
        using var key = Registry.CurrentUser.OpenSubKey("Software\\Microsoft\\Windows\\CurrentVersion\\Run");
        return key?.GetValue("CardapioPrinterAgent") is string;
    }

    private void SetAutoStart(bool enabled)
    {
        using var key = Registry.CurrentUser.OpenSubKey("Software\\Microsoft\\Windows\\CurrentVersion\\Run", writable: true);
        if (key is null) return;
        if (enabled) key.SetValue("CardapioPrinterAgent", $"\"{Application.ExecutablePath}\"");
        else key.DeleteValue("CardapioPrinterAgent", throwOnMissingValue: false);
    }
}

internal sealed record AgentConfig(string BaseUrl, string ProtectedToken, string DeviceName, string? PrinterName);
internal sealed record JobResponse(List<PrintJobDto> Jobs);
internal sealed record PrintJobDto(long Id, string Kind, TicketPayload Payload);
internal sealed record TicketPayload(
    [property: JsonPropertyName("order_id")] long OrderId,
    [property: JsonPropertyName("created_at")] string CreatedAt,
    [property: JsonPropertyName("venue_name")] string VenueName,
    [property: JsonPropertyName("customer_name")] string CustomerName,
    string Channel,
    List<TicketItem> Items,
    string? Notes,
    string Total);
internal sealed record TicketItem(string Name, int Quantity, List<string> Options, string? Notes);

internal sealed class PairingForm : Form
{
    private readonly TextBox _url = new() { Text = "https://cardapiodigitalbh.com.br", Width = 330 };
    private readonly TextBox _code = new() { MaxLength = 8, Width = 130, CharacterCasing = CharacterCasing.Upper };
    private readonly TextBox _name = new() { Text = Environment.MachineName, Width = 220 };

    public PairingForm()
    {
        Text = "Conectar computador da loja";
        Width = 410;
        Height = 270;
        StartPosition = FormStartPosition.CenterScreen;
        FormBorderStyle = FormBorderStyle.FixedDialog;
        MaximizeBox = false;
        MinimizeBox = false;
        var layout = new TableLayoutPanel { Dock = DockStyle.Fill, Padding = new Padding(16), ColumnCount = 1, RowCount = 7 };
        layout.Controls.Add(new Label { Text = "Endereço do sistema", AutoSize = true });
        layout.Controls.Add(_url);
        layout.Controls.Add(new Label { Text = "Código de 8 caracteres gerado no painel", AutoSize = true });
        layout.Controls.Add(_code);
        layout.Controls.Add(new Label { Text = "Nome deste computador", AutoSize = true });
        layout.Controls.Add(_name);
        var button = new Button { Text = "Conectar computador", AutoSize = true, DialogResult = DialogResult.OK };
        button.Click += (_, _) => { BaseUrl = _url.Text; Code = _code.Text.Trim().ToUpperInvariant(); DeviceName = _name.Text.Trim(); };
        layout.Controls.Add(button);
        Controls.Add(layout);
        AcceptButton = button;
    }

    public string BaseUrl { get; private set; } = "";
    public string Code { get; private set; } = "";
    public string DeviceName { get; private set; } = "";
}
