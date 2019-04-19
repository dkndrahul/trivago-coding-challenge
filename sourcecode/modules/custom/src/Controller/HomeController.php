<?php

namespace Drupal\custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * HomeController
 * 
 * Admin credentials: admin | admin@ 
 * 
 * Tasks: 
 * #1 We provide you a RSS feed with a list of wines and the day when they’re available.
 * You need to import it and store the available on it. There should be a way to update
 * the available wines every day. 
 * >>Rss feed are imported into drupal nodes using drupal batch queue. 
 * In "Wines" page the list of imported rss feed data are shown. 
 * To manually import rss feed please click "Import rss feed" button.
 * 
 * >>@see, \Drupal\custom\Controller\HomeController::importBatch(), 
 *         \Drupal\custom\Controller\HomeController::importBatchProcess(), 
 *         \Drupal\custom\Controller\HomeController::importBatchFinished(),
 *          custom_cron() 
 * >>@url, "/wines-list" 
 * 
 * #2 Create some code, preferrably in PHP, and use a queueing system for establishing
 * the communication between the waiters and the sommelier. Remember returning a 
 * response to the customer, either the wine is available or not. 
 * >>Start Typing in the "Wines" Field, Using ajax autocomplete a list of wines 
 * will appear. Select a wine. Then using Drupal asynchronous queue the availability 
 * of wine is checked and a success or error message will appear as response. 
 * Availability check is based on Order Date = Wine pubDate and Order Wine nid = Wine nid. 
 *     
 * >>@see, custom_form_alter(), custom_field_widget_form_alter(), 
 *         custom_field_wine_nids_available(), custom_field_wine_nids_validate() 
 * >>@url, node/add/orders?destination=orders 
 * 
 * #3 Be creative, add any other requirement that could be interesting for this 
 * feature.
 * >>Displaying a list of Wines, filter wines, sort wines, Import Feed, Edit, Delete Wine. 
 *   @url,  "/wines-list" page [Menu = "Wines"]
 * >>Displaying a list of orders, filter orders, Add, Edit, Delete Order. 
 *   @url, "/orders" page [Menu = "Orders"]
 * >>Display order details in modal dialog using ajax.  
 *   @url, "/orders" page [Menu = "Orders"]
 * 
 */
class HomeController extends ControllerBase {

  /**
   * Landing Page. 
   * if user is not authenticated then goto login page 
   * OR goto Orders page
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public static function homePage() {
    if (\Drupal::currentUser()->isAnonymous()) {
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }
    
    return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromRoute('view.vw_orders.page_1', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Manually Import Rss feed using batch process.
   * @return type
   */
  public static function importBatch() {
    $batch = [
      'title' => t('Importing Wine Feed'),
      'operations' => [
        ['\Drupal\custom\Controller\HomeController::importBatchProcess', []]
      ],
      'init_message' => t('Scanning Wine Rss Feed: @link ', ['@link' => 'https://www.winespectator.com/rss/rss?t=dwp']),
      'progress_message' => t('Processed @current wines out of @total wines.'),
      'error_message' => t('An error occurred during processing'),
      'finished' => '\Drupal\custom\Controller\HomeController::importBatchFinished',
    ];
    batch_set($batch);
    return batch_process('wines-list');
  }

  /**
   * Read the Rss file from 
   * https://www.winespectator.com/rss/rss?t=dwp 
   * and save into "wines" node.
   * @param type $context
   */
  public static function importBatchProcess(&$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      //load feed 
      $xml = new \SimpleXMLElement('https://www.winespectator.com/rss/rss?t=dwp', 0, TRUE);
      $context['sandbox']['max'] = 0;
      $context['sandbox']['items_xml'] = [];
      if ($xml->channel->item) {
        $context['sandbox']['max'] = $xml->channel->item->count();
        $context['sandbox']['items_xml'] = [];
        foreach ($xml->channel->item as $delta => $item) {
          $context['sandbox']['items_xml'][] = [
            'title' => htmlentities($item->title),
            'link' => htmlentities($item->link),
            'guid' => htmlentities($item->guid),
            'pubDate' => htmlentities($item->pubDate),
          ];
        }
      }
    }


    $items_left = $context['sandbox']['max'] - $context['sandbox']['progress'];
    //when calling from cron process all items
    if($context['trigger_by_cron']){
      $length = $context['sandbox']['max'];
    } else {
      //chunk 10 items and process it
      $length = ($items_left < 10 ? $items_left : 10);
    }
    
    for ($length; $length > 0; $length--) {
      $index = $context['sandbox']['progress'];
      $item = $context['sandbox']['items_xml'][$index];
      $existing_node = \Drupal::entityQuery('node')
          ->condition('type', 'wines')
          ->condition('status', 1)
          ->condition('title', $item['title'])
          ->execute()
      ;
      if (!empty($existing_node)) {
        //update existing node
        $node = \Drupal\node\Entity\Node::load(key($existing_node));
      }
      else {
        //create new
        $node = \Drupal\node\Entity\Node::create(['type' => 'wines', 'status' => 1]);
        $node->enforceIsNew();
      }

      //saving into node "Wines"
      $node->setTitle($item['title']);
      $node->set('field_guid', $item['guid']);
      $node->set('field_link', $item['link']);
      //UTC timezone
      $date = new DrupalDateTime($item['pubDate'], 'UTC');
      $node->set('field_pubdate', $date->format('Y-m-d'));
      $node->setPublished();
      $node->save();
      $context['results'][] = $node->id() . '::' . $node->label();

      $context['sandbox']['progress'] ++;
    }

    $context['message'] = t('@title', ['@title' => array_pop($context['results'])]);

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Display success message after batch import 
   * completed.
   * @param type $success
   * @param type $results
   * @param type $operations
   */
  public static function importBatchFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addStatus(\Drupal::translation()
              ->formatPlural(count($results), 'One wine processed.', '@count wines saved successfully.')
      );
    }
    else {
      \Drupal::messenger()->addStatus(t('Finished with an error.'));
    }
  }

}
//class
