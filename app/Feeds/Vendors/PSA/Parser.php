<?php

namespace App\Feeds\Vendors\PSA;

use App\Feeds\Parser\ShopifyParser;
use App\Helpers\StringHelper;

class Parser extends ShopifyParser
{
    private const PRODUCT_PAGE = 'https://myplushusa.com/collections/retail';

    public function getMpn(): string
    {
        return $this->getText( '.product p.h3' );
    }

    public function getCostToUs(): float
    {
        return StringHelper::getMoney( $this->getMoney( 'p.price' ) );
    }

    public function getImages(): array
    {
        return [ self::PRODUCT_PAGE . $this->getAttr( 'div.product__image-wrapper img', 'src' ) ];
    }
}
