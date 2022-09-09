=== Payment Gateway - Mpesa for WooCommerce  ===
Contributors: karson9
Author URI: http://turbohost.co.mz/
Plugin URL: https://wordpress.org/plugins/wc-m-pesa-payment-gateway/
Tags:  mpesa, woocommerce, payment gateway, Vodacom,  Mpesa API Mozambique
Requires at least: 5.0
Tested up to: 6.0
Requires PHP: 7.1
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Adiciona Mpesa como método de pagamento no WooCommerce.

== Description ==

O plugin *Mpesa for WooCommerce* é uma extensão para WooCommerce e WordPress que permite que você receba pagamentos diretamente em sua loja virtual através da API M-Pesa da Vodacom Moçambique.
Por meio do *Mpesa for WooCommerce*, os compradores de sua loja podem comprar produtos ou serviços em sua loja usando um número de telefone associado à conta M-Pesa.


== Other Notes ==

### Pré requisitos
Para usar o plugin é necessário:
* Ter [WooCommerce](https://wordpress.org/plugins/woocommerce) instalado.
* Criar uma conta no [portal de desenvolvedores do M-pesa](https://developer.mpesa.vm.co.mz/) onde irá obter as credenciais necessárias para configurar a conta.
* Solicite ao provedor de hospedagem que abra a conexão de saída no firewall para a porta *18352*. Caso esteja a usar qualquer dos planos de hospedagem da [TurboHost](https://turbohost.co.mz), a porta está aberta por padrão.

### Dúvidas

Se tiver  alguma dúvida :

* Visite a nossa sessão de [Perguntas Frequentes](https://wordpress.org/plugins/wc-m-pesa-payment-gateway/#faq).
* Crie um tópico no [fórum de ajuda do WordPress](https://wordpress.org/support/plugin/wc-m-pesa-payment-gateway/).
* Envie uma mensagem no grupo do WhatsApp ["WordPress Moçambique"](https://chat.whatsapp.com/EAxWq4Pljx9KH6Dc2VhxaU).

### Contribuir

Você pode contribuir com o código fonte em nossa [página do GitHub](https://github.com/turbohost-co/wc-mpesa-payment-gateway).


Este plugin foi desenvolvido sem nenhum incentivo da Vodacom. Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

 
== Installation ==

### Instalação automática

1. Faça login no seu painel do WordPress
2. Clique em *Plugins> Adicionar novo* no menu esquerdo.
3. Na caixa de pesquisa, digite **Mpesa for WooCommerce**.
4. Clique em *Instalar agora* no **Mpesa for WooCommerce** para instalar o plug-in no seu site e em seguida clique em  *ativar* o plug-in.
5. Clique em *WooCommerce> Configurações* no menu esquerdo e clique na guia *Pagamentos*.
6. Clique em **Mpesa for WooCommerce** na lista dos métodos de pagamento disponíveis
7. Defina as configurações do Mpesa for WooCommerce usando credenciais disponíveis em https://developer.mpesa.vm.co.mz/

 

### Instalação manual

Caso a instalação automática não funcione, faça o download do plug-in aqui usando o botão Download.

1. Descompacte o arquivo e carregue a pasta via FTP no diretório *wp-content/plugins* da sua instalação do WordPress.
2. Vá para *Plugins> Plugins instalados* e clique em *Ativar* no Mpesa for WooCommerce.

== Screenshots ==


1. Lista dos método de pagamento com o  *Mpesa for WooCommerce* ativo
2. Configuração das credenciais método de pagamento Mpesa.
3. Página da Finalização do pagamento com o método de pagamento selecionado com o campo para digitar o número do telefone mpesa
4. Página de pagamento com as instruções para que o cliente finalize o pagamento.

== Frequently Asked Questions ==

= Onde encontro as credenciais para configurar o plug-in? =

Para obter credenciais, crie uma conta em https://developer.mpesa.vm.co.mz/

= O que devo colocar no campo Código do provedor de serviços? =

* Se você estiver no ambiente de teste, use **171717**
* Se você estiver no ambiente de produção, use código de produção fornecido pela Vodacom.

= Não recebo notificação no processo de pagamento. O que deve ser?

Se ao fazer o pedido de pagamento, não recebe nenhuma notificação e da timeout no final, provavelmente é resultado do firewall no servidor que está bloqueando as portas de saída. Solicite ao provedor de hospedagem que abra a conexão de saída para a porta 18352.

== Changelog ==

= 1.3.4 =

Correção de bugs

= 1.3.0 =

* Correção de bugs
* Melhoria na exibição das respostas das solicitações de pagamentos

= 1.2.1 = 

Correção de bug na validação de prefixos Mpesa e aprimoramento da performance

= 1.2 =

* Feedback de erro aprimorado na página de pagamento
* Corrigido o erro de validação do certificado do servidor em ambientes *Windows*

= 1.0 =

Primeiro lançamento
