<?php

namespace App\Feeds\Vendors\AFF;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;


class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ 'div#emthemesModez-verticalCategories ul li.navPages-item a' , '.pagination-list a'];
    public const PRODUCT_LINK_CSS_SELECTORS = [ 'h4.card-title a' ];

    protected array $first = [ 'https://www.affinitechstore.com' ];
    
    public function isValidFeedItem( FeedItem $fi ): bool
    {
        return !empty( $fi->getMpn() );
    }

}