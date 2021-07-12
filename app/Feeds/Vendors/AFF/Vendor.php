<?php

namespace App\Feeds\Vendors\AFF;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\ParserCrawler;


class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ 'div#emthemesModez-verticalCategories ul li.navPages-item a' ];
    public const PRODUCT_LINK_CSS_SELECTORS = [ 'h4.card-title a', '#product-listing-container .pagination ul.pagination-list li a' ];

    protected array $first = [ 'https://www.affinitechstore.com' ];

    public function getProductsLinks(Data $data, string $url): array
    {
        $links = [];
        $crawler = new ParserCrawler( $data->getData());
        if($crawler->exists('.pagination')){
            $page = 2;
//            https://www.affinitechstore.com/dome-cameras/?sort=pricedesc&page=2
            while(true){
                $pagination_url = "$url?page=$page";
                $pager_data = $this->getDownloader()->get($pagination_url);
                if(str_contains($pager_data->getData(), '404 Error - Page not found')){
                    break;
                }
                $links[] = parent::getProductsLinks($pager_data, $pagination_url);
                $page++;
            }
        }
        return array_merge_recursive($links, parent::getProductsLinks($data, $url));
    }

    public function isValidFeedItem( FeedItem $fi ): bool
    {
        return !empty( $fi->getMpn() );
    }

}