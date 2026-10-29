# Agente de impressao para Windows

O agente roda na bandeja do Windows, consulta trabalhos pela conexao HTTPS de saida e envia o comprovante para uma impressora instalada no computador. Ele nao abre portas de entrada.

## Preparar o computador da loja

1. Instale no Windows o driver oficial da impressora USB. Para Epson TM-T20, use a fila criada pelo instalador Epson e configure o papel termico correto nas preferencias do Windows.
2. No painel do estabelecimento, abra **Impressoras** e gere um codigo de pareamento.
3. Baixe e execute `CardapioPrinterAgent.exe`, informe o endereco do sistema, o codigo e um nome para o computador.
4. No icone do agente perto do relogio, escolha a fila da impressora e marque **Iniciar com o Windows**.
5. No painel, defina este computador como impressora principal. Um pedido imprime quando a equipe o aceita e a fila permite acompanhar o resultado.

O agente usa qualquer modelo que o Windows consiga imprimir por um driver instalado, inclusive impressoras USB, de rede e modelos compativeis com Epson ESC/POS. Sem driver do fabricante ou do Windows, nao ha garantia de compatibilidade universal.

## Gerar o aplicativo

No Windows com o SDK .NET 8:

```powershell
dotnet publish .\CardapioPrinterAgent.csproj -c Release -r win-x64 --self-contained true -p:PublishSingleFile=true -o .\publish
```

O token do computador fica protegido pelo DPAPI do usuario Windows que fez o pareamento. O agente nao registra esse token em logs. A impressao fisica nao pode garantir exatamente uma copia se o computador desligar no instante entre imprimir e confirmar; nesses casos, confira a ultima bobina antes de repetir o pedido.
