<?php

return [
    'order' => [
        'pending' => 'Pendente', 'accepted' => 'Aceito', 'preparing' => 'Em preparo',
        'ready' => 'Pronto', 'out_for_delivery' => 'Saiu para entrega', 'completed' => 'Concluído',
        'cancelled' => 'Cancelado', 'refunded' => 'Estornado',
    ],
    'channel' => ['table' => 'Mesa', 'pickup' => 'Retirada', 'delivery' => 'Entrega'],
    'payment' => ['pix' => 'PIX', 'pix_external' => 'PIX', 'cash' => 'Dinheiro', 'debit' => 'Cartão de débito', 'credit' => 'Cartão de crédito', 'mercadopago' => 'Mercado Pago online', 'voucher' => 'Vale'],
    'role' => ['owner' => 'Proprietário', 'platform_owner' => 'Proprietário da plataforma', 'waiter' => 'Garçom', 'kitchen' => 'Cozinha', 'cashier' => 'Caixa'],
    'instance' => ['connected' => 'Conectado', 'connecting' => 'Conectando', 'disconnected' => 'Desconectado', 'degraded' => 'Instável'],
    'movement' => ['reserve' => 'Reserva', 'consume' => 'Consumo', 'release' => 'Liberação', 'entry' => 'Entrada', 'loss' => 'Perda'],
    'event' => ['order_received' => 'Pedido recebido', 'order_accepted' => 'Pedido aceito', 'order_preparing' => 'Pedido em preparo', 'order_ready' => 'Pedido pronto', 'order_out_for_delivery' => 'Pedido saiu para entrega', 'order_completed' => 'Pedido concluído', 'order_cancelled' => 'Pedido cancelado'],
    'feature' => [
        'catalog' => 'Cardápio', 'tables' => 'Mesas', 'inventory' => 'Estoque', 'ingredients' => 'Ingredientes',
        'delivery' => 'Entrega', 'pickup' => 'Retirada', 'geofence' => 'Geolocalização', 'whatsapp' => 'WhatsApp',
        'printing' => 'Impressão', 'reports' => 'Relatórios', 'payments' => 'Pagamentos', 'banners' => 'Banners', 'marketplace' => 'Diretório',
    ],
    'audit' => [
        'tenant.plan_updated' => 'Plano do estabelecimento atualizado',
        'tenant.impersonation_started' => 'Acesso de suporte iniciado',
        'tenant.impersonation_finished' => 'Acesso de suporte encerrado',
        'whatsapp.instance_reset' => 'Instância do WhatsApp redefinida',
        'plan.created' => 'Plano criado',
        'plan.updated' => 'Plano atualizado',
    ],
];
