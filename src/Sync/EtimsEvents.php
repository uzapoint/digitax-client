<?php

namespace Uzapoint\Digitax\Sync;

/**
 * RabbitMQ exchanges and routing keys used by the eTIMS integration.
 * One place, so publishers and consumers cannot drift apart.
 */
final class EtimsEvents
{
    // Exchanges
    public const SETTINGS_EXCHANGE = 'settings.events';
    public const PRODUCTS_EXCHANGE = 'products.events';
    public const SALES_EXCHANGE = 'sales.events';
    public const STOCKS_EXCHANGE = 'stocks.events';
    public const PROCUREMENT_EXCHANGE = 'procurement.events';
    public const ETIMS_EXCHANGE = 'etims.events';

    /** settings-ms -> all: full eTIMS config for one business. */
    public const CONFIG_UPDATED = 'settings.etims.updated';

    /** products-ms -> sales, procurement, stocks: a SKU's DigiTax state changed. */
    public const SKU_SYNCED = 'products.skus.etims_synced';

    /** settings-ms -> products, sales, procurement: DigiTax callback received. */
    public const CALLBACK_RECEIVED = 'etims.callback.received';

    /** sales-ms -> stocks: re-check DigiTax stock for these items. */
    public const STOCK_RECONCILE_REQUESTED = 'etims.stock.reconcile.requested';

    /** sales-ms -> pos: a sale (or credit note) was fiscalised. */
    public const SALE_SYNCED = 'sales.etims.synced';

    /** sales-ms -> pos: a POS sale exists and has its sale_number. */
    public const POS_SALE_CREATED = 'sales.pos_sale.created';

    /** procurement-ms -> all: a goods receipt was linked or reverse-invoiced. */
    public const GRN_SYNCED = 'procurement.grn.etims_synced';
}
