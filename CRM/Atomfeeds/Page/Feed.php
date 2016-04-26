<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 | Adaptions for atom feeeds Copyright Chirojeugd Vlaanderen vzw 2016 |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CRM/Core/Page.php';

/**
 * Page for atom feed dashlet.
 * 
 * This started as a copy of CRM/Dashlet/Page/Blog.php.
 */
class CRM_Atomfeeds_Page_Feed extends CRM_Core_Page {

  const CHECK_TIMEOUT = 5;
  const CACHE_DAYS = 1;

  /**
   * Get the final, usable URL string (after interpolating any variables)
   *
   * @return FALSE|string
   */
  public function getBlogUrl() {
    $urls = CRM_Core_BAO_Setting::getItem('atomfeed', 'feed_urls');
    
    // For the moment, we only look at the first URL.
    $url = CRM_Utils_Array::first($urls);
    
    return CRM_Utils_System::evalUrl($url);
  }  

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Feed'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    // Copied the below from CRM/Dashlet/Page/Blog.php
    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'dashlet');
    $this->assign('context', $context);

    $this->assign('blog', $this->_getBlog());
    parent::run();
  }

  /**
   * Load blog articles from cache.
   * Refresh cache if expired
   *
   * @return array
   */
  private function _getBlog() {
    // Fetch data from cache
    // TODO: Once we support multiple feeds, we need to store the feed
    // ID or something like that in the cache.
    $cache = CRM_Core_DAO::executeQuery("SELECT data, created_date FROM civicrm_cache
      WHERE group_name = 'dashboard' AND path = 'feed'");
    if ($cache->fetch()) {
      $expire = time() - (60 * 60 * 24 * self::CACHE_DAYS);
      // Refresh data after CACHE_DAYS
      if (strtotime($cache->created_date) < $expire) {
        $new_data = $this->_getFeed($this->getBlogUrl());
        // If fetching the new feed was successful, return it
        // Otherwise use the old cached data - it's better than nothing
        if ($new_data) {
          return $new_data;
        }
      }
      return unserialize($cache->data);
    }
    return $this->_getFeed($this->getBlogUrl());
  }

  /**
   * Parse rss feed and cache results.
   *
   * @param $url
   *
   * @return array|NULL
   *   array of blog items; or NULL if not available
   */
  public function _getFeed($url) {
    $httpClient = new CRM_Utils_HttpClient(self::CHECK_TIMEOUT);
    list ($status, $rawFeed) = $httpClient->get($url);
    if ($status !== CRM_Utils_HttpClient::STATUS_OK) {
      return NULL;
    }
    $feed = @simplexml_load_string($rawFeed);

    $blog = array();
    
    $items = NULL;
    if (!empty($feed->channel->item)) {
      // RSS
      $items = $feed->channel->item;
    } else if (!empty($feed->entry)) {
      // Atom
      $items = $feed->entry;
    }
    if ($feed && !empty($items)) {
      foreach ($items as $item) {
        $item = (array) $item;
        // Clean up description - remove tags that would break dashboard layout
        $description = "";
        if (!empty($item['description'])) {
          // RSS
          $description = $item['description'];
        }
        else if (!empty($item['content'])) {
          // Atom
          $description = $item['content'];
          unset($item['content']);
        }
        $description = preg_replace('#<h[1-3][^>]*>(.+?)</h[1-3][^>]*>#s', '<h4>$1</h4>', $description);
        $item['description'] = strip_tags($description, "<a><p><h4><h5><h6><b><i><em><strong><ol><ul><li><dd><dt><code><pre><br/>");
        if (!is_string($item['link'])) {
          $link = $item['link']['href']->__toString();
          $item['link'] = $link;
          $item['author'] = $item['author']->Name->__toString();
        }
        $blog[] = $item;
      }
      if ($blog) {
        CRM_Core_BAO_Cache::setItem($blog, 'dashboard', 'blog');
      }
    }
    return $blog;
  }  
  
  /**
   * Use the same template as the native CiviCRM Blog dashlet.
   * @return string
   */
  public function getTemplateFileName() {
    return "CRM/Dashlet/Page/Blog.tpl";
  }  
}
