<?php
/**
 * Developer: Habeeb Amode
 */

require 'vendor/autoload.php';
$httpClient = new \Goutte\Client();



class SearchResult
{
    /**
     * @var httpClient
     * Goutte instance 
     */
    protected $httpClient;

     /**
     * @var keyword
     * search result keyword
     */
    protected $keyword;

    /**
     * @var searchResultURI
     * search result uri
     */
    protected $searchResultURI;
    
    /**
     * @arg keyword
     * construct
     */
    public function __construct(string $keyword)
    {
        $this->httpClient = new \Goutte\Client();
        $this->keyword = $keyword; 
    }

    /**
     * get search result uri after page search
     * @return string
     */
    private function searchResultBaseUri():string
    {
        try {
            $crawler = $this->httpClient->request('GET', 'https://search.ipaustralia.gov.au/trademarks/search/advanced');
            $form = $crawler->filter('#basicSearchForm')->first()->form();
            $crawler = $this->httpClient->submit($form, array('wv[0]' => $this->keyword));
            $result = (array) $crawler;
            $resultValue = array_values($result);
            $searchResultURI = $resultValue[0];
            return $searchResultURI; 
        } catch (\Exception $e) {
           return "Check your network connection";
        }
        
    }

    /**
     * return scrapped search result page data
     */
    public function scrapeResultWebPage()
    {
        $resultPageScraped= $this->httpClient->request('GET', $this->searchResultBaseUri()); 
        return $resultPageScraped;
    }

    /**
     * return number of paginated search result 
     */
    public function countSearchResultPagination($resultPageScraped)
    {
        // get page number
        $pageNumber = "";
        $resultPageScraped->filter('.col div.results-count .qa-count')->each(function ($node) use (&$pageNumber) {
            $pageNumber = $node->text();
        });
        // convert string number to integer
        $totalNumber = (int) str_replace(",", "", $pageNumber);
        // break total number per pagination
        $pageCount = $totalNumber / 100;
        return $pageCount;
    }

    /**
     * return all Search results
     */
    public function searchResult($pageCount)
    {
        $paginationCount  = ceil($pageCount); 
        $totalSearchResult = $pageCount * 100;
        $searchResultURI = $this->searchResultBaseUri();
        $contents = [];
        for ($page=0; $page < $paginationCount; $page++) {
            $response = $this->httpClient->request('GET', "$searchResultURI&p=$page");
            // get page number
            $index = [];  
            $response->filter('.result .number a')->each(function ($node) use (&$index) {
                $index[] = $node->text();
            });

            //get logo url
            $logo_url = [];
            $response->filter('.result td.image img')->each(function ($node) use (&$logo_url) {
                $logo_url[] = $node->attr('src');
            });

            //get name
            $name = [];
            $response->filter('.result .words')->each(function ($node) use (&$name) {
                $name[] = $node->text();
            });

            //get classes
            $classes = [];
            $response->filter('.result .classes')->each(function ($node) use (&$classes) {
                $classes[] = $node->text();
            });

            //get status
            $status = [];
            $response->filter('.result td.status')->each(function ($node) use (&$status) {
                $status[] = $node->text();
            });

            //get pageUrl
            $pageUrl=[];
            $response->filter('.result td.number')->each(function ($node) use (&$pageUrl) {
                $pageUrl[] = $node->attr('href');
            });

            //search result total
            $contents['counts'] = $totalSearchResult;
            // loop through data
            $allData = [];
            for ($i=0; $i< count($index); $i++) {
                $allData[$i] =  [
                "number"=>$index[$i]??"",
                "logo_url"=>$logo_url[$i]??"",
                "name"=>$name[$i]??"",
                "status"=>$status[$i]??"",
                "details_page_url"=>$pageUrl[$i]??""
            ];
            
            $contents[$page] = $allData;
            }
            
            var_dump($contents);
            
        }   
        

    }
}

// command: php scrape_serp.php lak

$keyword =(string) $argv[1];

$auSearchResult = (new SearchResult($keyword));
$scrappedSearchResultPage = $auSearchResult->scrapeResultWebPage();
$countSRPagination = $auSearchResult->countSearchResultPagination($scrappedSearchResultPage);
$searchResult = $auSearchResult->searchResult($countSRPagination);

  



 
