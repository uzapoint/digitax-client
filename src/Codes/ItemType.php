<?php

namespace Uzapoint\Digitax\Codes;

final class ItemType
{
    public const RAW_MATERIAL = '1';
    public const FINISHED_PRODUCT = '2';
    public const SERVICE = '3';

    public const LABELS = [
        self::RAW_MATERIAL => 'Raw material',
        self::FINISHED_PRODUCT => 'Finished product',
        self::SERVICE => 'Service',
    ];
}
