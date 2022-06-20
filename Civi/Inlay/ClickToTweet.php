<?php
namespace Civi\Inlay;

use Civi;
use Civi\Inlay\ApiRequest;
use Civi\Inlay\Type as InlayType;
use CRM_Inlayctt_ExtensionUtil as E;
// use Civi\Core\Event\GenericHookEvent;
use Civi\Inlay\ApiException;
// use CRM_Utils_System;

class ClickToTweet extends InlayType {

  const SPAM_DELAY_SECONDS = 2;

  public static $typeName = 'Click to Tweet';

  public static $defaultConfig = [
    // The template if we can't find the MP.
    'tweet_generic' => '',
    // The template if we found the MP but don't have ctt data
    'tweet_mp' => '',
    // The template assuming we have full data
    'tweet_template' => '',
  ];

  /**
   * Note: because of the way CRM.url works, you MUST put a ? before the #
   *
   * @var string
   */
  public static $editURLTemplate = 'civicrm/a?#/inlays/ctt/{id}';

  /**
   * Generates data to be served with the Javascript application code bundle.
   *
   * @return array
   */
  public function getInitData() :array {

    $data = [
      'init'           => 'inlayCTTInit',
      'tweet_generic'  => $this->config['tweet_generic'],
    ];


    return $data;
  }

  /**
   */
  public function extractTwitterHandleFromUrl(string $url) :string {
    if (!preg_match('@^https://twitter.com/([^/?#]+)@', $url, $matches)) {
      return '';
    }
    return '@' . $matches[1];
  }

  /**
   * Process a request
   *
   * Request data is just key, value pairs from the form data. If it does not
   * have 'token' field then a token is generated and returned. Otherwise the
   * token is checked and processing continues.
   *
   * @param \Civi\Inlay\Request $request
   * @return array
   *
   * @throws \Civi\Inlay\ApiException;
   */
  public function processRequest(ApiRequest $request) :array {
    $input = $request->getBody();
    switch ($input['need']) {
    case 'campaignFromPostcode':
      return $this->getCampaignFromPostcode($input);

    default:
      throw new \Exception("error unrecognised need");
    }
  }

  /**
   * Look up the MP from the constituency, then look up the tweet template and substitute the values.
   *
   * on success, array with keys: mpName, mpTwitter ('@username'), tweet.
   *
   * @throws Civi\Inlay\ApiException if MP not found, or such.
   */
  public function getCampaignFromPostcode(array $request) :array {
    $result = [];
    if (empty($request['postcode'])) {
      throw new ApiException(400, ['error' => 'Missing postcode data [CTT1]'], "Dodgy looking request without postcode");
    }
    if (empty($request['parliamentary_constituency']) || !is_string($request['parliamentary_constituency'])) {
      throw new ApiException(400, ['error' => 'Missing parliamentary_constituency data [CTT2]'], "Dodgy looking request without parliamentary_constituency");
    }

    // Look up the MP in this parliamentary_constituency
    $contacts = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'display_name', 'twitter.url')
      ->addJoin('Website AS twitter', 'LEFT', ['twitter.website_type_id', '=', 11])
      ->addWhere('MPs_Info.Constituency', '=', $request['parliamentary_constituency'])
      ->addWhere('contact_type', '=', 'Individual')
      ->addWhere('contact_sub_type', '=', 'MP')
      ->addWhere('is_deceased', '=', FALSE)
      ->addOrderBy('modified_date', 'DESC')
      ->execute();

    $c = count($contacts);
    $mp = $contacts->first();
    if ($c > 1) {
      \Civi::log()->notice("inlayctt: Multiple MPs for constituency $request[parliamentary_constituency] Using last modified: $mp[display_name] ($mp[id])");
    }
    else if ($c === 0) {
      throw new ApiException(400, 'Could not find MP for constituency', "inlayctt: No MP for constituency $request[parliamentary_constituency]");
    }

    $result += [
      'mpName' => $mp['display_name'],
      'mpTwitter' => $this->extractTwitterHandleFromUrl($mp['twitter.url'] ?? ''),
    ];
    if (empty($result['mpTwitter'])) {
      $result += [
        'tweet' => $this->config['tweet_generic']
      ];
      \Civi::log()->notice("inlayctt: No Twitter website for MP for $request[parliamentary_constituency] Using generic tweet.");
      return $result;
    }

    // It's unlikely that we'll have a problematic number of tweet content for
    // a while, so not donig addwhere on that: just fetch all the data for now.

    // Look up the data for this campaign.
    $cttData = \Civi\Api4\CustomValue::get('inlayctt_content', FALSE)
      ->addWhere('entity_id', '=', $mp['id'])
      ->execute()
      ->indexBy('inlayctt_field')
      ->column('inlayctt_content');

    Civi::log()->info("inlayctt: custom data for $mp[display_name] $mp[id]: " . json_encode($cttData));

    $missing = [];
    $callback = function($matches) use ($result, $cttData, &$missing) {
      switch ($matches[1] ?? '') {
      case 'mpName':
      case 'mpTwitter':
        $_ = $result[$matches[1]] ?? '';
        break;

      default:
        $_ = $cttData[$matches[1]] ?? '';
        break;

      }
      if (empty($_)) {
        $missing[] = $matches[1];
      }
      return $_;
    };
    // First try the full template
    $result['tweet'] = preg_replace_callback('/{([a-zA-Z0-9_]+)}/', $callback, $this->config['tweet_template']);

    if ($missing) {
      // Failed on at least one thing, fall back to the tweet_mp one.
      $result['tweet'] = preg_replace_callback('/{([a-zA-Z0-9_]+)}/', $callback, $this->config['tweet_mp']);
      \Civi::log()->info("inlayctt: got MP, but missing some data. Constituency: $request[parliamentary_constituency]: "
        .json_encode(['tpl' => $this->config['tweet_mp'], 'result' => $result, 'cttData' => $cttData, 'missing' => $missing], JSON_PRETTY_PRINT));
    }
    else {
      // Successful response
      \Civi::log()->info("inlayctt: success response for Constituency: $request[parliamentary_constituency]: "
        .json_encode(['result' => $result, 'cttData' => $cttData], JSON_PRETTY_PRINT));
    }

    return $result;
  }

  /**
   * Get the Javascript app script.
   *
   * This will be bundled with getInitData() and some other helpers into a file
   * that will be sourced by the client website.
   *
   * @return string Content of a Javascript file.
   */
  public function getExternalScript() :string {
    return file_get_contents(E::path('js/inlay-ctt.js'));
  }

}

