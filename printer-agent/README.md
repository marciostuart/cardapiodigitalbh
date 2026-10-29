# Agente de impressão para Windows

O agente fica na bandeja do Windows e busca trabalhos por HTTPS. Ele usa o spooler e o driver instalado, sem abrir portas de entrada. O pacote é self-contained: o computador da loja não precisa instalar o SDK .NET.

## Instalar em um computador da loja

1. Instale o driver oficial da impressora USB. Para Epson TM-T20, selecione a fila criada pelo instalador Epson e configure o papel térmico nas preferências do Windows.
2. Baixe e extraia o ZIP inteiro do agente.
3. Abra a pasta extraída e execute **Instalar.bat**. Não precisa de administrador.
4. No menu Iniciar, abra **Cardápio Digital BH → Agente de Impressão**.
5. No painel do estabelecimento, abra **Impressoras** e gere um código de pareamento.
6. No agente, informe o endereço HTTPS, o código e o nome deste computador.
7. No menu do ícone perto do relógio, escolha a fila da impressora e marque **Iniciar com o Windows**. No painel, defina este computador como impressora principal.

O instalador verifica o SHA-256 do executável e instala no diretório de programas do usuário. Configuração, token protegido por DPAPI e histórico de impressão ficam em `%LOCALAPPDATA%/CardapioDigitalBH`.

## Atualizar

1. No ícone do agente perto do relógio, escolha **Sair**.
2. Extraia o novo ZIP inteiro.
3. Execute **Instalar.bat** na pasta nova. O instalador substitui o executável e preserva o pareamento e o histórico local.

## Desinstalar

1. No painel, desative ou remova o computador da lista de impressoras.
2. Feche o agente pelo menu **Sair**.
3. Execute **Desinstalar.bat**. Por padrão, os dados locais são preservados.

Para apagar também o pareamento e o histórico deste computador, execute:

~~~powershell
$uninstaller = Join-Path $env:LOCALAPPDATA 'Programs/Cardapio Digital BH/Agente de Impressao/Uninstall-PrinterAgent.ps1'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File $uninstaller -RemoveLocalData
~~~

## Gerar um pacote

No Windows com SDK .NET 8, na raiz do repositório:

~~~powershell
./printer-agent/Package-PrinterAgent.ps1 -Version 1.0.0
~~~

O ZIP e seu checksum ficam em `%TEMP%/CardapioPrinterAgent-packages`. O pacote pode ser instalado ou usado para atualizar o agente.

O agente imprime em qualquer modelo que o Windows reconheça por um driver instalado, inclusive impressoras USB, de rede e modelos compatíveis com ESC/POS. A compatibilidade de cada combinação de impressora e driver precisa ser confirmada em piloto físico.

O token do computador fica protegido pelo DPAPI do usuário e não é registrado em logs. A impressão física não pode garantir exatamente uma cópia se o computador desligar no instante entre imprimir e confirmar; nesses casos, confira a última bobina antes de repetir o pedido.
