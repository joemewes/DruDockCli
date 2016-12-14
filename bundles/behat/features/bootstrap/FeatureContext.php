<?php

use Behat\Behat\Context\ClosuredContextInterface;
use Behat\Behat\Context\TranslatedContextInterface;
use Behat\Behat\Context\BehatContext;

use Behat\Behat\Event\SuiteEvent;
use Behat\Behat\Event\FeatureEvent;
use Behat\Behat\Event\ScenarioEvent;

use Behat\Behat\Event\StepEvent;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;
use Behat\MinkExtension\Context\RawMinkContext;

use Behat\Behat\Exception\PendingException;

use Behat\Mink\Exception\ElementException;
use Behat\Mink\Exception\ElementNotFoundException;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Drupal\Component\Utility\Random;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

use Alex\MailCatcher\Behat\MailCatcherContext;



/**
 * Some of our features need to run their scenarios sequentially
 * and we need a way to pass relevant data (like generated node id)
 * from one scenario to the next.  This class provides a simple
 * registry to pass data. This should be used only when absolutely
 * necessary as scenarios should be independent as often as possible.
 */
abstract class HackyDataRegistry {
  public static $data = array();
  public static function set($name, $value) {
    self::$data[$name] = $value;
  }
  public static function get($name) {
    $value = "";
    if (isset(self::$data[$name])) {
      $value = self::$data[$name];
    }
    if ($value === "") {
      $backtrace = debug_backtrace(FALSE, 2);
      $calling = $backtrace[1];
      if (array_key_exists('line', $calling) && array_key_exists('file', $calling)) {
        throw new PendingException(sprintf("Fix HackyDataRegistry accessing with unset key at %s:%d in %s.", $calling['file'], $calling['line'], $calling['function']));
      } else {
        // Disabled primarily for calls from AfterScenario for now due to too many errors.
        //throw new PendingException(sprintf("Fix HackyDataRegistry accessing with unset key in %s.", $calling['function']));
      }
    }
    return $value;
  }
  public static function keyExists($name) {
    if (isset(self::$data[$name])) {
      return TRUE;
    }
    return FALSE;
  }
}

class LocalDataRegistry {
  public $data = array();
  public function set($name, $value) {
    $this->data[$name] = $value;
  }
  public function get($name) {
    $value = "";
    if (isset($this->data[$name])) {
      $value = $this->data[$name];
    }
    return $value;
  }
}

/**
 * Features context.
 */
class FeatureContext extends Drupal\DrupalExtension\Context\DrupalContext
{
  protected $users = array();
  /**
   * Initializes context.
   * Every scenario gets its own context object.
   *
   * @param array $parameters context parameters (set them up through behat.yml)
   */
  public function __construct(array $parameters)
  {
    $this->dataRegistry = new LocalDataRegistry();
    $this->random = new Random();

    if (isset($parameters['drupal_users'])) {
      $this->drupal_users = $parameters['drupal_users'];
    }

    if (isset($parameters['email'])) {
      $this->email = $parameters['email'];
    }
    $this->mailAddresses = array();
    $this->mailMessages = array();

    if (isset($parameters['selectors'])) {
      $this->selectors = $parameters['selectors'];
    }
  }

  /**
   * @Given /^I am on the "([^"]*)"$/
   */
  public function iAmOnThe($arg1)
  {
      throw new PendingException();
  }

  /**
   * Hold the execution until the page is/resource are completely loaded OR timeout
   *
   * @Given /^I wait until the page (?:loads|is loaded)$/
   * @param object $callback
   *   The callback function that needs to be checked repeatedly
   */
  public function iWaitUntilThePageLoads($callback = null) {
    // Manual timeout in seconds
    $timeout = 60;
    // Default callback
    if (empty($callback)) {
      if ($this->getSession()->getDriver() instanceof Behat\Mink\Driver\GoutteDriver) {
        $callback = function($context) {
          // If the page is completely loaded and the footer text is found
          if(200 == $context->getSession()->getDriver()->getStatusCode()) {
            return true;
          }
          return false;
        };
      }
      else {
        // Convert $timeout value into milliseconds
        // document.readyState becomes 'complete' when the page is fully loaded
        $this->getSession()->wait($timeout*1000, "document.readyState == 'complete'");
        return;
      }
    }
    if (!is_callable($callback)) {
      throw new Exception('The given callback is invalid/doesn\'t exist');
    }
    // Try out the callback until $timeout is reached
    for ($i = 0, $limit = $timeout/2; $i < $limit; $i++) {
      if ($callback($this)) {
        return true;
      }
      // Try every 2 seconds
      sleep(2);
    }
    throw new Exception('The request is timed out');
  }

 /**
  * @Given /^I wait (\d+) second(?:s|)$/
  */
 public function iWaitSeconds($arg1) {
   sleep($arg1);
 }

  /**
   * @Given /^I should be logged in$/
   */
  public function iShouldBeLoggedIn() {
    if (!$this->loggedIn()) {
      return false;
    }
  }

  /**
   * @Given /^I should not be logged in$/
   */
  public function iShouldNotBeLoggedIn() {
    if ($this->loggedIn()) {
      return false;
    }
  }

  /**
   * Determine if the a user is already logged in.
   * Override DrupalContext::loggedIn() because we display logout link in the dropdown.
   */
  public function loggedIn() {
    $session = $this->getSession();
    $session->visit($this->locatePath('/'));
    // If a logout link is found, we are logged in. While not perfect, this is
    // how Drupal SimpleTests currently work as well.
    $element = $session->getPage();
    sleep(1);
    return $element->findLink($this->getDrupalText('log_out'));
  }

  /**
   * @Given /^I am logged in as the "([^"]*)" with the password "([^"]*)"$/
   * @Given /^I log in as the "([^"]*)" with the password "([^"]*)"$/
   */
  public function iAmLoggedInAsTheWithThePassword($username, $password) {
    return array (
      new Given("I fill in \"Username or e-mail address\" with \"$username\""),
      new Given("I fill in \"Password\" with \"$password\""),
      new Given("I press \"Log in\""),
    );
  }



  /**
   * @Given /^I fill in "([^"]*)" with random text$/
   */
  public function iFillInWithRandomText($label) {
    // A @Tranform would be more elegant.
    $randomString = strtolower($this->random->name(10));
    // Save this for later retrieval.
    HackyDataRegistry::set('random:' . $label, $randomString);
    $step = "I fill in \"$label\" with \"$randomString\"";
    return new Then($step);
  }

  /**
   * @Given /^I should not see the following <texts>$/
   */
  public function iShouldNotSeeTheFollowingTexts(TableNode $table) {
    $page = $this->getSession()->getPage();
    $table = $table->getHash();
    foreach ($table as $key => $value) {
      $text = $table[$key]['texts'];
      if(!$page->hasContent($text) === FALSE) {
        throw new Exception("The text '" . $text . "' was found");
      }
    }
  }

  /**
   * @Given /^I (?:should |)see the following <texts>$/
   */
  public function iShouldSeeTheFollowingTexts(TableNode $table) {
    $page = $this->getSession()->getPage();
    $messages = array();
    $failure_detected = FALSE;
    $table = $table->getHash();
    foreach ($table as $key => $value) {
      $text = $table[$key]['texts'];
      if($page->hasContent($text) === FALSE) {
        $messages[] = "FAILED: The text '" . $text . "' was not found";
        $failure_detected = TRUE;
      } else {
        $messages[] = "PASSED: '" . $text . "'";
      }
    }
    if ($failure_detected) {
      throw new Exception(implode("\n", $messages));
    }
  }

  /**
   * @Given /^I (?:should |)see the following <links>$/
   */
  public function iShouldSeeTheFollowingLinks(TableNode $table) {
    $page = $this->getSession()->getPage();
    $table = $table->getHash();
    foreach ($table as $key => $value) {
      $link = $table[$key]['links'];
      $result = $page->findLink($link);
      if(empty($result)) {
        throw new Exception("The link '" . $link . "' was not found");
      }
    }
  }

  /**
   * @Given /^I should not see the following <links>$/
   */
  public function iShouldNotSeeTheFollowingLinks(TableNode $table) {
    $page = $this->getSession()->getPage();
    $table = $table->getHash();
    foreach ($table as $key => $value) {
      $link = $table[$key]['links'];
      $result = $page->findLink($link);
      if(!empty($result)) {
        throw new Exception("The link '" . $link . "' was found");
      }
    }
  }

  /**
   * @When /^I hover over the element "([^"]*)"$/
   */
  public function iHoverOverElement($locator){
    $session = $this->getSession();
    $element = $session->getPage()->find('css', $locator); // runs the actual query and returns the element

    // errors must not pass silently
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
    }

    // ok, let's hover it
    $element->mouseOver();
  }

  /**
   * @Then /^I test if the element "([^"]*)" is visible$/
   */
  public function IsElementVisible($target){
    $session = $this->getSession();
    $element = $session->getPage()->find('css', $target);
    if($element->isVisible()){
      $element->click();
    }
  }

  /**
   * @Then /^I hover over the element "([^"]*)" and click "([^"]*)"$/
   */
  public function  IHoverAndClick($hoverRegion, $targetClick){
    $session = $this->getSession();

    $containerDiv = $session->getPage()->find('css', $hoverRegion);
    $containerDiv->click();
    if($containerDiv->isVisible()){
      foreach ($containerDiv->findAll('css',  'ul') as $list) {
        $listItem = $list->findAll('css', 'li');
        foreach($listItem as $item){
          if($targetClick == $item->getText()) {
            $item->click();
            return;
          }
        }
      }
    }
  }

  /**
   * Function to check if the field specified is outlined in red or not
   *
   * @Given /^the field "([^"]*)" should be outlined in red$/
   *
   * @param string $field
   *   The form field label to be checked.
   */
  public function theFieldShouldBeOutlinedInRed($field) {
    $page = $this->getSession()->getPage();
    // get the object of the field
    $formField = $page->findField($field);
    if (empty($formField)) {
      throw new Exception('The page does not have the field with label "' . $field . '"');
    }
    // get the 'class' attribute of the field
    $class = $formField->getAttribute("class");
    // we get one or more classes with space separated. Split them using space
    $class = explode(" ", $class);
    // if the field has 'error' class, then the field will be outlined with red
    if (!in_array("error", $class)) {
      throw new Exception('The field "' . $field . '" is not outlined with red');
    }
  }

  /**
   * Return email address for given user role.
   */
  protected function getMailAddress($user) {

    if(empty($this->mailAddresses[$user])) {
      $now = time();
      $this->mailAddresses[$user] = $this->email['username'] . '+' . $now . '@'. $this->email['host'];
    }

    return $this->mailAddresses[$user];
  }

  /**
   * @Given /^I fill in "([^"]*)" with "([^"]*)" address$/
   */
  public function iFillInWithAddress($label, $user) {
    $mail_address = $this->getMailAddress($user);
    $this->getSession()->getPage()->fillField($label, $mail_address);
  }

  /**
   * @Given /^the "([^"]*)" user received an email '([^']*)'$/
   */
  public function theUserReceivedAnEmail($user, $title) {
    sleep(3);
    $mail_address = $this->getMailAddress($user);
    $title = $this->fixStepArgument($title);

    $mbox = imap_open( $this->email['mailbox'], $mail_address,  $this->email['password']);
    $all = imap_check($mbox);

    $received = false;
    // Trying 100 times with three seconds pause
    for ($attempts = 0; $attempts++ < 10; ) {

      if ($all->Nmsgs) {
        foreach (imap_fetch_overview($mbox, "1:$all->Nmsgs") as $msg) {
          if ($msg->to == $mail_address && strpos($msg->subject, $title) !== FALSE) {
            $msg->body = imap_fetchbody($mbox, $msg->msgno, 1);
            // Consider if we start sending HTML emails.
            //$msg->body['html'] = imap_fetchbody($mbox, $msg->msgno, 2);
            $this->mailMessages[$user][] = $msg;
            imap_delete($mbox, $msg->msgno);
            $received = true;
            break 2;
          }
        }
      }
      sleep(10);
    }
    imap_close($mbox);
    // Throw Exception if message not found.
    if (!$received) {
      throw new \Exception('Email "' . $title . '" to "' . $mail_address . '" not received.');
    }
  }

  /**
   * @Given /^the "([^"]*)" user have not received an email '([^']*)'$/
   */
  public function theUserNotReceivedAnEmail($user, $title) {
    $mail_address = $this->getMailAddress($user);
    $title = $this->fixStepArgument($title);

    $mbox = imap_open( $this->email['mailbox'], $mail_address,  $this->email['password']);

    $all = imap_check($mbox);

    $received = false;
    // Trying 10 times with three seconds pause
    for ($attempts = 0; $attempts++ < 10; ) {

      if ($all->Nmsgs) {
        foreach (imap_fetch_overview($mbox, "1:$all->Nmsgs") as $msg) {
          if ($msg->to == $mail_address && strpos($msg->subject, $title) !== FALSE) {
            $msg->body = imap_fetchbody($mbox, $msg->msgno, 1);
            // Consider if we start sending HTML emails.
            //$msg->body['html'] = imap_fetchbody($mbox, $msg->msgno, 2);
            $this->mailMessages[$user][] = $msg;
            imap_delete($mbox, $msg->msgno);
            $received = true;
            break 2;
          }
        }
      }
      sleep(3);
    }
    imap_close($mbox);
    if ($received) {
      throw new \Exception('Email "' . $title . '" to "' . $mail_address . '" has been received.');
    }
  }


  /**
   * @Given /^that the user "([^"]*)" is not registered$/
   */
  public function thatTheUserIsNotRegistered($user_name) {
    try {
      $driver = $this->getDrupalParameter('api_driver');
      if (!$driver) {
        return;
      }
      if ('drupal' !== $driver) {
        throw new \Exception('Must use the Drupal driver to clean up these scenarios.');
      }
      if ($user = user_load_by_name($user_name)) {
        $this->getDriver($driver)->userDelete($user);
      }
    }
    catch (Exception $e) {
      if(strpos($e->getMessage(), 'Unable to find') < 1){
        print $e->getMessage();
      }
    }
  }

  /**
   * @Given /^"([^"]*)" option in "([^"]*)" should be disabled$/
   */
  public function optionInShouldBeDisabled($option_key, $label) {

    $page = $this->getSession()->getPage();
    $select = $page->find('xpath', "//label[contains(., '$label')]/following-sibling::select");

    if ($select) {
      $dom = new domDocument;
      $dom->loadHTML($select->getHtml());
      $options = $dom->getElementsByTagName('option');
      foreach ($options as $option) {
        if($option->getAttribute('disabled') && $option->nodeValue == $option_key) {
          return;
        }
      }
    }

    throw new ElementNotFoundException(
      $this->getSession(), 'select option', 'value|text', $option_key
    );

  }

  /**
   * @Given /^"([^"]*)" option in "([^"]*)" should be selected$/
   */
  public function optionInShouldBeSelected($option_key, $label) {

    $page = $this->getSession()->getPage();
    $select = $page->find('xpath', "//label[contains(., '$label')]/following-sibling::select");

    if ($select) {
      $dom = new domDocument;
      $dom->loadHTML($select->getHtml());
      $options = $dom->getElementsByTagName('option');
      foreach ($options as $option) {
        if($option->getAttribute('selected') && $option->nodeValue == $option_key) {
          return;
        }
      }
    }

    throw new ElementNotFoundException(
      $this->getSession(), 'select option', 'value|text', $option_key
    );

  }

  /**
   * @when /^I click the text "([^"]*)" in the location "([^"]*)" within the region of "([^"]*)" titled "([^"]*)"$/
   */
  public function clickTextWithinRegion($target, $location, $region, $parentTitle){
    $ctx = $this->getSession()->getPage();
    foreach ($ctx->findAll('css', $region) as $link) {
      if($link->getText() == $parentTitle){
        $targetTD = $link->getParent();
        $ids = $targetTD->findAll('css', $location);
        foreach($ids as $id){
          if($id->getText() == $target){
            $id->click();
            return;
          }
        }
      }
    }
  }

  /**
   * @When /^I select "([^"]*)" from "([^"]*)" select list$/
   */
  public function iSelectOptionFromList($option, $select){
    $select = $this->fixStepArgument($select);
    $option = $this->fixStepArgument($option);

    $page = $this->getSession()->getPage();
    $field = $page->findField($select, true);

    if (null === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value', $select);
    }

    $id = $field->getAttribute('id');
    $opt = $field->find('named', array('option', $option));
    $val = $opt->getValue();

    $javascript = "jQuery('#$id').val('$val');
                  jQuery('#$id').trigger('chosen:updated');
                  jQuery('#$id').trigger('change');";

    $this->getSession()->executeScript($javascript);
  }

  /**
   * @Then /^I should see the modal "([^"]*)"$/
   */
  public function iShouldSeeTheModal($css) {
    $session = $this->getSession();
    $containerDiv = $session->getPage()->find('css', $css);
    if($containerDiv == null){
      throw new \Exception(sprintf("Modal with id|name|label|value not found with value $css"));
    }
  }

  /**
   * @Given /^I select "([^"]*)" from "([^"]*)" chosen\.js select box$/
   */
  public function iSelectFromChosenJsSelectBox($option, $select) {
    $select = $this->fixStepArgument($select);
    $option = $this->fixStepArgument($option);

    $page = $this->getSession()->getPage();
    $field = $page->findField($select, true);

    if (null === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value', $select);
    }

    $id = $field->getAttribute('id');
    $opt = $field->find('named', array('option', $option));
    $val = $opt->getValue();

    $javascript = "jQuery('#$id').val('$val');
                  jQuery('#$id').trigger('chosen:updated');
                  jQuery('#$id').trigger('change');";

    $this->getSession()->executeScript($javascript);
  }

  /**
   * @Then /^I (?:|should )see page title "(?P<title>[^"]*)"$/
   */
  public function assertPageTitle($title) {
    $results = $this->getSession()->getPage()->findAll('css', 'h1.page-header');
    foreach ($results as $result) {
      if ($result->getText() == $title) {
        return;
      }
    }
    throw new \Exception(sprintf("The text '%s' was not found in page title on the page %s", $title, $this->getSession()->getCurrentUrl()));
  }

  /**
   * @Then /^I (?:|should )see node title "(?P<title>[^"]*)"$/
   */
  public function assertNodeTitle($title) {
    $results = $this->getSession()->getPage()->findAll('css', 'article.node h1.node-title');
    foreach ($results as $result) {
      if ($result->getText() == $title) {
        return;
      }
    }
    if (count($results)) {
      throw new \Exception(sprintf("The text '%s' was not found in node title on the page %s", $title, $this->getSession()->getCurrentUrl()));
    }
    else {
      throw new \Exception(sprintf("Node title missing on the page %s", $title, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Given /^view "([^"]*)" should have "([^"]*)" row(?:s|)$/
   */
  public function viewShouldHaveRows($view_display_id, $rows) {
    $view = $this->getSession()->getPage()->find('css', '.view-display-id-' . $view_display_id);
    if (empty($view)) {
      throw new \Exception('View with display id "' . $view_display_id . '" not found.');
    }
    $view_rows = $view->findAll('css', '.views-row');
    if (count($view_rows) != $rows) {
      throw new \Exception('View with display id "' . $view_display_id . '" has ' . count($view_rows) . ' rows instead of ' . $rows. '.');
    }
  }

  /**
   * @Given /^pager in "([^"]*)" view should match "([^"]*)"$/
   */
  public function pagerInViewShouldMatch($view_display_id, $regex) {
    $view = $this->getSession()->getPage()->find('css', '.view-display-id-' . $view_display_id);
    if (empty($view)) {
      throw new \Exception('View with display id "' . $view_display_id . '" not found.');
    }
    $pager = $view->find('css', '.pagination');

    if (empty($pager)) {
      throw new \Exception('View "' . $view_display_id . '" doesn\' have a pager.');
    }

    $text = $pager->getText();
    preg_match('/' . $regex . '/i', $text, $match);

    if (empty($match)) {
      throw new Exception('Pager in view "' . $view_display_id. '" contains "' . $text . '" which doesn\'t match "' . $regex . '"');
    }
  }

  /**
   * @Given /^pager should match "([^"]*)"$/
   */
  public function pagerShouldMatch($regex) {
    $pager = $this->getSession()->getPage()->find('css', '.pagination');

    if (empty($pager)) {
      throw new \Exception('Pager not found.');
    }

    $text = $pager->getText();
    preg_match('/' . $regex . '/i', $text, $match);

    if (empty($match)) {
      throw new Exception('Pager contains "' . $text . '" which doesn\'t match "' . $regex . '"');
    }
  }

  /**
   * @Then /^"([^"]*)" field in row "([^"]*)" of "([^"]*)" view should match "([^"]*)"$/
   */
  public function fieldInRowOfViewShouldMatch($field_name, $row, $view_display_id, $regex) {
    $view = $this->getSession()->getPage()->find('css', '.view-display-id-' . $view_display_id);
    if (empty($view)) {
      throw new \Exception('View with display id "' . $view_display_id . '" not found.');
    }

    $view_row = $view->find('css', '.views-row-' . $row);
    if (empty($view_row)) {
      throw new \Exception('Row "' . $row . '" in view "' . $view_display_id . '" not found.');
    }

    $field = $view_row->find('css', '.views-field-' . $field_name);

    if (empty($field)) {
      throw new \Exception('Field "' . $field_name. '" in row "' . $row . '" of view "' . $view_display_id . '" not found.');
    }

    $text = $field->getText();
    preg_match('/' . $regex . '/i', $text, $match);

    if (!$field->isVisible()) {
      throw new Exception('Field "' . $field_name. '" found but it\'s not visible');
    }
    elseif (empty($match)) {
      throw new Exception('Field "' . $field_name. '" found but it contains "' . $text . '" which doesn\'t match "' . $regex . '"');
    }
  }

  /**
   * @Then /^avatar in row "([^"]*)" of "([^"]*)" view should link to "([^"]*)"$/
   */
  public function avatarInRowOfViewShouldLinkTo($row, $view_display_id, $href) {
    $view = $this->getSession()->getPage()->find('css', '.view-display-id-' . $view_display_id);
    if (empty($view)) {
      throw new \Exception('View with display id "' . $view_display_id . '" not found.');
    }

    $view_row = $view->find('css', '.views-row-' . $row);
    if (empty($view_row)) {
      throw new \Exception('Row "' . $row . '" in view "' . $view_display_id . '" not found.');
    }

    $avatar = $view_row->find('css', '.field-avatar');
    $link = $avatar->findLink('');
    if (empty($link)) {
      throw new \Exception('Avatar in row "' . $row . '" of view "' . $view_display_id . '" is not a link.');
    }

    $href_property = $link->getAttribute('href');
    // Use regex to get relative url.
    // We expect relative urls as we set them in the HTML but selenium returns
    // Dom property instead of HTML attribute whis us absolute url in most cases
    // https://code.google.com/p/selenium/issues/detail?id=1824
    if (preg_replace('/http(s)?:\/\/[^\/]*/i', '', $href_property) != $href) {
      throw new \Exception('Avatar in row "' . $row . '" of view "' . $view_display_id . '" links to "' . $href_property . '" instead of "' . $href . '".');
    }

    $img = $link->find('css', 'img');
    if (empty($img)) {
      throw new \Exception('Avatar in row "' . $row . '" of view "' . $view_display_id . '" doesn\' contain user picture.');
    }
  }

  /** Click on the element with the provided css selector
   *
   * @When /^(?:|I )click on the element wit css selector "([^"]*)"$/
   */
  public function iClickOnTheElementWithCssSelector($locator)
  {
    $session = $this->getSession();
    $element = $session->getPage()->find('css', $locator);

    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
    }

    $element->click();
  }

  /**
   * @When /^I click "([^"]*)" field in row "([^"]*)" of "([^"]*)" view$/
   */
  public function clickFieldInRowOfView($field_name, $row, $view_display_id) {
    $view = $this->getSession()->getPage()->find('css', '.view-display-id-' . $view_display_id);
    if (empty($view)) {
      throw new \Exception('View with display id "' . $view_display_id . '" not found.');
    }

    $view_row = $view->find('css', '.views-row-' . $row);
    if (empty($view_row)) {
      throw new \Exception('Row "' . $row . '" in view "' . $view_display_id . '" not found.');
    }

    $field = $view_row->find('css', '.views-field-' . $field_name);

    if (empty($field)) {
      throw new \Exception('Field "' . $field_name. '" in row "' . $row . '" of view "' . $view_display_id . '" not found.');
    }

    $link = $field->findLink('');
    if (empty($link)) {
      throw new \Exception('Field "' . $field_name. '" in row "' . $row . '" of view "' . $view_display_id . '" is not a link.');
    }
    $link->click();
  }

  /**
   * @Then /^row "([^"]*)" of "([^"]*)" view should match "([^"]*)"$/
   */
  public function rowOfViewShouldMatch($row, $view_display_id, $regex) {
    $view = $this->getSession()->getPage()->find('css', '.view-display-id-' . $view_display_id);
    if (empty($view)) {
      throw new \Exception('View with display id "' . $view_display_id . '" not found.');
    }

    $view_row = $view->find('css', '.views-row-' . $row);
    if (empty($view_row)) {
      throw new \Exception('Row "' . $row . '" in view "' . $view_display_id . '" not found.');
    }

    $row_content = $view_row->getText();
    preg_match('/' . $regex . '/i', $row_content, $match);

    if (empty($match)) {
      throw new Exception('Row "' . $row . '" of view "' . $view_display_id . '" contains "' . $row_content . '" what doesn\'t match "' . $regex . '"');
    }
  }

  /**
   * @Then /^"([^"]*)" item in "([^"]*)" subnav should be active$/
   */
  public function itemInSubnavShouldBeActive($item, $menu) {
    $subnav = $this->getSession()->getPage()->find('css', '.subnav-' . strtolower($menu));
    if (empty($subnav)) {
      throw new \Exception('"' . $menu . '" sub navigation not found.');
    }
    elseif (!$subnav->isVisible()) {
      throw new Exception('"' . $menu . '" sub navigation is not active sub navigation');
    }

    $link = $subnav->findLink($item);
    if (empty($link)) {
      throw new \Exception('"' . $item . '" menu item not found.');
    }

    $classes = $link->getAttribute('class');
    if (strpos($classes,'active') === false) {
      throw new \Exception('"' . $item . '" menu item is not active.');
    }

  }

  /**
   * @Given /^there should be "([^"]*)" search results on the page$/
   */
  public function thereShouldBeSearchResultsOnThePage($expected_number) {
    $items = $this->getSession()->getPage()->findAll('css', '.search-results .search-result');
    if (empty($items)) {
      throw new \Exception('No search results found.');
    }
    if (count($items) != $expected_number) {
      throw new \Exception('There are ' . count($items) . ' search results instead of ' . $expected_number . '.');
    }
  }

  /**
   * @And /^(?:|I )select "(?P<option>\w+)" in the "(?P<name>\w+)" select$/
   */
  public function selectState($option, $name) {
    $page          = $this->getSession()->getPage();
    $selectElement = $page->find('xpath', '//select[@data-name = "' . $name . '"]');

    $selectElement->selectOption($option);
  }

  /**
   * @Given /^I have an image "([^"]*)" x "([^"]*)" pixels titled "([^"]*)" located in "([^"]*)" folder$/
   */
  public function iHaveAnImageTitledLocatedInFolder($width, $height, $title, $path) {
    $image = @imagecreatetruecolor($width, $height) or die('Cannot Initialize new GD image stream');

    $color = array(
      imagecolorallocate($image,rand(100, 150),rand(100, 150),rand(100, 150)),
      imagecolorallocate($image,rand(50, 100),rand(50, 100),rand(50, 100)),
    );
    for ($y = 0; $y < $height / 5; $y++) {
      $i=$y % 2;
      for ($x = 0; $x < $width / 5; $x++) {
        imagefilledrectangle($image, $x*5, $y*5, $x*5 + 5, $y*5 + 5, $color[++$i % 2]);
      }
    }

    imagestring($image, 5, $width/2 - strlen($title) * 4.5 , $height/2 - 15, $title, imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $path . '/' . $title . '.png');
    imagedestroy($image);
  }

  /**
   * @Given /^I have a txt file titled "([^"]*)" located in "([^"]*)" folder$/
   */
  public function iHaveATxtFileTitledLocatedInFolder($title, $path){
    $file = $path . $title;
    $contents = "Test txt file.";
    $handle = fopen($file, "w");
    if(!$handle){
      die("Can't open $file");
    }
    else{
      fwrite($handle, $contents);
      fclose($handle);
    }
  }

  /**
   * @Given /^I have following csv file titled "([^"]*)" located in "([^"]*)" folder:$/
   */
  public function iHaveFollowingCsvFileTitledLocatedInFolder($title, $path, TableNode $table) {
    $file = $path . $title;
    $fp = fopen($file,'w');

    if(!$fp) {
      die("Can't open $file.");
    }

    $rows = $table->getRows();
    foreach ($rows as $fields) {
      fputcsv($fp, $fields);
    }
    fclose($fp);

    if(file_exists($file) && filesize($file)){
      echo 'Notice: csv file created in repository/tests' . $file;
      //return $file;
    }else{
      die('There is not file to return.');
    }
  }

  /**
   * @When /^I click hidden button with the id "([^"]*)"$/
   */
  public function clickHiddenButton($searchField){
    // get context
    $this->getSession()
      ->getPage()
      ->find('css', '#' . $searchField)
      ->click();
  }

  /**
   * @When /^I check the file size of "([^"]*)"$/
   */
  public function checkFile($file){
    echo "Checking the file $file exists and the file size is greater than 0. \r\n";
    if(file_exists($file)){
      echo "The file $file does exist.\r\n";
    }else{
      echo "The file $file does not exist, please supply a valid file.\r\n";
      die();
    }

    if(filesize($file) > 0){
      echo "The file size is: " . filesize($file)  . ".\r\n";
    }else{
      echo "The file size for $file is " . filesize($file) . ".\r\n";
      die();
    }
    echo "End file check.";
  }

  /**
   * @Then /^I should see "([^"]*)" elements with the class "([^"]*)"$/
   */
  public function iShouldSeeElements($num, $element) {
    $container = $this->getSession()->getPage();
    $nodes = $container->findAll('css', '.' . $element);

    if (intval($num) > count($nodes)) {
      $message = sprintf('%d elements less than %s "%s" found on the page, but should be %d.', count($nodes), $selectorType, $selector, $count);
      throw new ExpectationException($message, $this->session);
    }
  }

  /**
   * @Then /^I should see "([^"]*)" in the "([^"]*)" data-module section "([^"]*)"$/
   */
  public function seeTextInSection($text, $class, $dataSection) {
    //Get context
    $ctx = $this->getSession()->getPage();
    foreach ($ctx->findAll('css', 'div.' . $class) as $section) {
      if(($section->hasAttribute('data-module')) && ($section->getAttribute('data-module') == $dataSection)){
        $moreLink = $section->find('css', 'div.more-link');
        if(isset($moreLink)){
          if($moreLink->getText() != $text){
            throw new Exception("$text could not be found in the $dataSection container");
          }
        }else{
          throw new Exception("$text could not be found in the $dataSection container");
        }
      }
    }
  }

  /**
   * @When /^I upload the csv file "([^"]*)" to "([^"]*)"$/
   * @Override MinkContext:attachFileToField
   */
  public function iUploadCSV($path, $location) {

    $field = str_replace('\\"', '"', $location);

    if (null === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value', $location);
    }

    if ($this->getMinkParameter('files_path')) {
      $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR).$path;

    } else {
      throw new Exception("File is not found at the given location");
    }

    $this->checkFile($fullPath);
    $this->getSession()->getPage()->attachFileToField($field, __DIR__ . $fullPath);

    echo "V2: attaching file $fullPath to field: $field \r\n";
  }

  /**
   * @When /^I take a screenshot "([^"]*)"$/
   */
  public function iTakeAScreenshot($name){
    $image_data = $this->getSession()->getDriver()->getScreenshot();
    $file_and_path = '/var/tmp/screenshots/step_'.$name.'_screenshot.jpg';
    file_put_contents($file_and_path, $image_data);

    echo 'Taking a screen shot. This can be found at "/var/tmp/screenshots/step_'.$name.'_screenshot.jpg"';

  }

  /**
   * @Then /^I should see an image with the class "([^"]*)" and the title "([^"]*)"$/
   */
  public function seeImageWithClassAndTitle($imageClass, $imageTitle) {
    //Get context
    $ctx = $this->getSession()->getPage();

    $ctxTitle = $ctx->find('css', $imageClass . ' img')->getAttribute('title');

    if($ctxTitle != $imageTitle){
      throw new \Exception("A image with the title $imageTitle and the class $imageClass could not be found on the page");
    }
  }

  /**
   * @Then /^I should see an image with the class "([^"]*)"$/
   */
  public function seeImageWithClass($imageClass) {
    //Get context
    $ctx = $this->getSession()->getPage();

    $ctxClass = $ctx->find('css', 'img.' . $imageClass);

    if($ctxClass != $imageClass){
      throw new \Exception("A image with the class $imageClass could not be found on the page");
    }
  }

  /**
   * @Then /^I should see an image with the title "([^"]*)"$/
   */
  public function seeImageWithTitle($imageTitle) {
    //Get context
    $ctx = $this->getSession()->getPage();

    $ctxTitle = $ctx->find('xpath', '//img[@title="'.$imageTitle.'"]')->getAttribute('title');

    if($ctxTitle != $imageTitle){
      throw new \Exception("A image with the title $imageTitle could not be found on the page");
    }
  }

  /**
   * @When /^I search for images with the class "([^"]*)" the title "([^"]*)" and alt text "([^"]*)"
   */
  public function searchAllImagesWith($imageClass, $imageTitle, $imageAlt){
    //Get context
    $ctx = $this->getSession()->getPage();

    foreach ($ctx->findAll('css', 'img.' . $imageClass) as $img) {
      //Compare the title attribute
      if ($img->hasAttribute('title')) {
        if($imageTitle != $img->getAttribute('title')){
          throw new \Exception("An image with the 'title' $imageTitle cannot be found on the page");
        }
      } else {
        throw new \Exception("An image with the 'title' attribute cannot be found on the page");
      }
      //Compare the alt title attribute
      if ($img->hasAttribute('alt')) {
        if($imageAlt != $img->getAttribute('alt')){
          throw new \Exception("An image with the 'title' $imageAlt cannot be found on the page");
        }
      } else {
        throw new \Exception("An image with the 'title' attribute cannot be found on the page");
      }
    }
  }

  /**
   * @When /^I search for the first occurrence of "([^"]*)" with the text "([^"]*)" in the region "([^"]*)"$/
   */
  public function searchTheFirstText($tag, $text, $region){
    // get context
    $session = $this->getSession()->getPage();
    $searchRegion = $session->find('css', $region);
    $searchTag = $searchRegion->find('css', $tag);
    $errorText = $searchTag->getText();
    if($searchTag->getText() !== $text){
      throw new \Exception("The text supplied '$text' does not match the first element with the tag '$tag' in this region.");
    }
  }

  /**
   * @when /^I click the first occurence of "([^"]*)" in "([^"]*)"$/
   */
  public function clickTheFirst($item, $container) {
    // get context
    $this->getSession()
         ->getPage()
         ->find('css', '.' . $container. ' .' . $item)
         ->click();
  }


  /**
   * @When /^I click on "([^"]*)"$/
   */
  public function iClickOnElementWithClassname($class) {
    $this->getSession()->getPage()->find("css", ".$class")->click();
  }

  /**
   * @When /^I click the text "([^"]*)" with the class "([^"]*)"$/
   */
  public function clickTextInRegion($text, $region){
    $ctx = $this->getSession()->getPage();
    foreach ($ctx->findAll('css', $region) as $link) {
      if($link->getText() == $text){
        $link->click();
      }
    }
  }

  /**
   * @when /^I click the label "([^"]*)" on a checkbox$/
   */
  public function clickLabelForCheckBox($labelText) {

    // Find the label by its text, then use that to get the radio item's ID
    $radioId = null;
    $ctx = $this->getSession()->getPage();

    /** @var $label NodeElement */
    foreach ($ctx->findAll('css', 'label') as $label) {
      if ($labelText === $label->getText()) {
        if ($label->hasAttribute('for')) {
          //$radioId = $label->getAttribute('for');
          $label->click();
          break;
        } else {
          throw new \Exception("Radio button's label needs the 'for' attribute to be set");
        }
      }
    }
  }

  /**
   * @Given /^user "([^"]*)" created "([^"]*)" titled "([^"]*)"$/
   */
  public function userCreatedTitled($user_name, $node_type, $title) {
    try {

      $drush = $this->getDriver();
      $uid = $drush->drush('ev', array('"\$user = user_load_by_name(\'' . $user_name . '\'); print \$user->uid;"'));

      $uid = 1;
      $node_type = 'ranking_institution';
      $title = 'title2';

      $drush->drush('ev', array('"
        \$values = array(\'type\' => \'' . $node_type . '\',
        \'uid\' => \'' . $uid . '\',
        \'status\' => \'1\',
        \'comment\' => \'0\',);
        \$entity = entity_create(\'node\', \$values);
        \$wrapper = entity_metadata_wrapper(\'node\', \$entity);
        \$wrapper->title->set(\'' . $title . '\');
        \$wrapper->save();
        "'));

    }
    catch (Exception $e) {
      throw new \Exception('PHP evaluation failed. ' . $e->getMessage());
    }
  }

  /**
   * @Given /^I execute function "([^"]*)"$/
   */
  public function iExecuteFunction($function) {

    try {
      $drush = $this->getDriver();
      $x = $drush->drush('ev', array('"' . $function . '"'));
      print $x;
    }
    catch (Exception $e) {
      throw new \Exception('PHP evaluation failed. ' . $e->getMessage());
    }
  }

  /**
   * @Given /^I switch to iframe "([^"]*)"$/
   */
  public function iSwitchToIframe($name) {
    $this->getSession()->switchToIFrame($name);
  }

  /**
   * @Given /^I switch to main frame$/
   */
  public function iSwitchToMainFrame() {
    $this->getSession()->switchToIFrame();
  }

  /**
   * @Given /^I browse "([^"]*)"$/
   */
  public function iBrowse($label) {
    $javascript = "var id = jQuery('label:contains(\'" . $label .  "\')').attr('for');
                  jQuery('#' + id + ' a.browse').click();
                  ";

    $this->getSession()->executeScript($javascript);
  }

  /**
   * @Given /^I press "([^"]*)" in media browser$/
   */
  public function iPressInMediaBrowser($value) {
    $javascript = "
                  jQuery('input[value=\'" . $value . "\']').click();
                  ";

    $this->getSession()->executeScript($javascript);
  }

  /**
   * @When I scroll :elementId into view
   */
  public function scrollIntoView($elementId) {
    $function = <<<JS
(function(){

  var elem = document.getElementById("$elementId");
  //elem.scrollIntoView(false);
  elem.scrollTop;
})()
JS;
    try {
      $this->getSession()->executeScript($function);
    }
    catch(Exception $e) {
      throw new \Exception("ScrollIntoView failed");
    }
  }

  /**
   * @When /^I scroll to the top of the page$/
   */
  public function scrollToTop(){
    $function = <<<JS
(function(){
  document.body.scrollTop = document.documentElement.scrollTop = 0;
})()
JS;
    try {
      $this->getSession()->executeScript($function);
    }
    catch(Exception $e) {
      throw new \Exception("ScrollIntoView failed");
    }
  }

  /**
   * @Then /^the metatag attribute "(?P<attribute>[^"]*)" should have the value "(?P<value>[^"]*)"$/
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   */
  public function assertMetaRegion($property, $content) {
    $page = $this->getSession()->getPage();
    $path = '/head/meta[@property="' . $property . '"][@content="' . $content . '"]';
    $element = $page->find('xpath', $path, TRUE);
    if (empty($element)) {
      throw new \Exception(sprintf('No "%s" Metatag on the page %s', $property, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Click some text
   *
   * @When /^I click on the text "([^"]*)"$/
   */
  public function iClickOnTheText($text)
  {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', '*//*[text()="'. $text .'"]')
    );
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Cannot find text: "%s"', $text));
    }
    $element->click();

  }

  /**
   * @When /^I click on the list item "([^"]*)" in the parent class "([^"]*)"$/
   */
  public function iClickOnTheLi($text, $parentClass) {
    $ctx = $this->getSession()->getPage();
    foreach ($ctx->findAll('css',  "$parentClass") as $list) {
      $listItem = $list->findAll('css', 'li');
      foreach($listItem as $item){
        if($text == $item->getText()) {
          $item->click();
        }
      }
    }
  }

   /**
   * Focus on field
   *
   * @When /^I focus on field "([^"]*)"$/
   */
  public function iFocusOnField($field)
  {
    $element = $this->getSession()->getPage()->find('css', $field);
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Cannot find field: "%s"', $field));
    }
    $element->focus();
  }



  /**
   * Get the instance variable to use in Javascript.
   *
   * @param string
   *   The instanceId used by the WYSIWYG module to identify the instance.
   *
   * @throws Exeception
   *   Throws an exception if the editor doesn't exist.
   *
   * @return string
   *   A Javascript expression representing the WYSIWYG instance.
   */
  protected function getWysiwygInstance($instanceId) {
    $instance = "Drupal.wysiwyg.instances['$instanceId']";

    if (!$this->getSession()->evaluateScript("return !!$instance")) {
      throw new \Exception(sprintf('The editor "%s" was not found on the page %s', $instanceId, $this->getSession()->getCurrentUrl()));
    }

    return $instance;
  }

  /**
   * Get a Mink Element representing the WYSIWYG toolbar.
   *
   * @param string
   *   The instanceId used by the WYSIWYG module to identify the instance.
   * @param string
   *   Identifies the underlying editor (for example, "tinymce").
   *
   * @throws Exeception
   *   Throws an exception if the toolbar can't be found.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The toolbar DOM Node.
   */
  protected function getWysiwygToolbar($instanceId, $editorType) {
    $driver = $this->getSession()->getDriver();

    if ($editorType == 'ckeditor') {
      $toolbarElement = $driver->find("//span[@class='cke_toolbox_main']");
      $toolbarElement = !empty($toolbarElement) ? $toolbarElement[0] : NULL;
    }
    else {
      $toolbarElement = $driver->find("//div[@id='{$instanceId}_toolbargroup']");
      $toolbarElement = !empty($toolbarElement) ? $toolbarElement[0] : NULL;
    }

    if (!$toolbarElement) {
      throw new \Exception(sprintf('Toolbar for editor "%s" was not found on the page %s', $instanceId, $this->getSession()->getCurrentUrl()));
    }

    return $toolbarElement;
  }

  /**
   * @When /^I type "([^"]*)" in the "([^"]*)" WYSIWYG editor$/
   */
  public function iTypeInTheWysiwygEditor($text, $instanceId) {
    $instance = $this->getWysiwygInstance($instanceId);
    $this->getSession()->executeScript("$instance.insert(\"$text\");");
  }

  /**
   * @When /^I fill in the "([^"]*)" WYSIWYG editor with "([^"]*)"$/
   */
  public function iFillInTheWysiwygEditor($text, $instanceId) {
    $instance = $this->getWysiwygInstance($instanceId);
    $this->getSession()->executeScript("$instance.setContent(\"$text\");");
  }

  /**
   * @When /^I click the "([^"]*)" button in the "([^"]*)" WYSIWYG editor$/
   */
  public function iClickTheButtonInTheWysiwygEditor($action, $instanceId) {
    $driver = $this->getSession()->getDriver();

    $instance = $this->getWysiwygInstance($instanceId);
    $editorType = $this->getSession()->evaluateScript("return $instance.editor");
    $toolbarElement = $this->getWysiwygToolbar($instanceId, $editorType);

    // Click the action button.
    $button = $toolbarElement->find("xpath", "//a[starts-with(@title, '$action')]");
    $button->click();
    $driver->wait(1000, TRUE);
  }

  /**
   * @When /^I expand the toolbar in the "([^"]*)" WYSIWYG editor$/
   */
  public function iExpandTheToolbarInTheWysiwygEditor($instanceId) {
    $driver = $this->getSession()->getDriver();

    $instance = $this->getWysiwygInstance($instanceId);
    $editorType = $this->getSession()->evaluateScript("return $instance.editor");
    $toolbarElement = $this->getWysiwygToolbar($instanceId, $editorType);

    // TODO: This is tinyMCE specific. We should probably switch on
    // $editorType.
    $action = 'Show/hide toolbars';

    // Expand wysiwyg toolbar.
    $button = $toolbarElement->find("xpath", "//a[starts-with(@title, '$action')]");
    if (strpos($button->getAttribute('class'), 'mceButtonActive') !== FALSE) {
      $button->click();
    }
  }

  /**
   * @Then /^I should see "([^"]*)" in the "([^"]*)" WYSIWYG editor$/
   */
  public function assertContentInWysiwygEditor($text, $tag, $region) {
    $instance = $this->getWysiwygInstance($instanceId);
    $content = $this->evaluateScript("return $instance.getContent()");
    if (strpos($text, $content) === FALSE) {
      throw new \Exception(sprintf('The text "%s" was not found in the "%s" WYSWIYG editor on the page %s', $text, $instanceId, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then /^I should not see "([^"]*)" in the "([^"]*)" WYSIWYG editor$/
   */
  public function assertContentNotInWysiwygEditor($text, $tag, $region) {
    $instance = $this->getWysiwygInstance($instanceId);
    $content = $this->evaluateScript("return $instance.getContent()");
    if (strpos($text, $content) !== FALSE) {
      throw new \Exception(sprintf('The text "%s" was found in the "%s" WYSWIYG editor on the page %s', $text, $instanceId, $this->getSession()->getCurrentUrl()));
    }
  }


    /**
   * Attaches file to field with specified id|name|label|value.
   *
   * @When /^(?:|I )attach our file "(?P<path>[^"]*)" to "(?P<field>(?:[^"]|\\")*)"$/
   */

  public function attachFileToField($field, $path) {
      $field = $this->fixStepArgument($field);
      if ($this->getMinkParameter('files_path')) {
          $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
          if (is_file($fullPath)) {
              $path = $fullPath;
          }
      }

      $localFile = $path;
      $tempZip = tempnam('', 'WebDriverZip');
      $zip = new \ZipArchive();
      $zip->open($tempZip, \ZipArchive::CREATE);
      $zip->addFile($localFile, basename($localFile));
      $zip->close();

      $remotePath = $this->getSession()->getDriver()->getWebDriverSession()->file([
        'file' => base64_encode(file_get_contents($tempZip))
      ]);

      $samplefile = 'http://www-01.ibm.com/support/knowledgecenter/SVU13_7.2.1/com.ibm.ismsaas.doc/reference/AssetsImportCompleteSample.csv?lang=en-us';

      $this->getSession()->getPage()->attachFileToField($field, $remotePath);
  }
  /*
   * @When /^(?:|I )click on "(?P<text>.+)" link$/
   */
  public function clickOnLink($text)
  {
      $element = $this->getSession()->getPage()->find('xpath', '//a[text() = "' . $text . '"]');
      $element->click();
  }

  /**
   * Scroll to a certain element by TEXT content.
   *
   * Example: When I scroll to the "test" text
   *
   * @Given /^I scroll to the "(?P<text>.+)" text$/
   */
  public function iScrollToElement($text) {
     $js = 'var pos = jQuery( ":contains('.$text.'):last").offset();';
     $js .= 'var top = pos.top - 120;';
     $js .= 'var left = pos.left - 20;';
     $js .= 'window.scrollTo((left < 0 ? 0 : left), (top < 0 ? 0 : top));';
     $this->getSession()->executeScript($js);
  }

  /**
   * Helper function to find nodes by type and title.
   *
   * @param $invert Set to TRUE to return errors if the node is found
   *
   * @return node
   */
  public function nodeLoadByTitle($type, $title, $invert = FALSE) {
    $query = db_select('node', 'n');
    $query->condition('n.title', $title);
    $query->condition('n.type', $type);
    //$query->condition('n.status', 1);
    $query->fields('n', array('nid'));
    $query_result = $query->execute();
    $nid = NULL;
    foreach ($query_result as $record) {
      $nid = $record->nid;
    }
    $node_not_found = TRUE;
    if (!is_null($nid)) {
      $node = node_load($nid);
      if ($node) {
        $node_not_found = FALSE;
      }
    }
    if ($invert) {
      if (!$node_not_found) {
        throw new \Exception(sprintf('A %s node with the title %s exists and it should not.', $type, $title));
      }
    }
    else {
      if ($node_not_found) {
        throw new \Exception(sprintf('There is no %s node with the title %s.', $type, $title));
      }
      return $node;
    }
  }

  /**
   * @When /^I go to the "([^"]*)" node with the title "([^"]*)"$/
   */
  public function iGoToTheNodeWithTheTitle($type, $title) {
    $node = $this->nodeLoadByTitle($type, $title);
    $path = 'node/' . $node->nid;
    $this->getSession()->visit($this->locatePath($path));
  }

   /**
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 3 minutes (180,000 ms).
   *
   * @Given /^I wait for the batch job to finish$/
   */
  public function iWaitForTheBatchJobToFinish() {
    $this->getSession()->wait(180000, 'jQuery("#updateprogress").length === 0');
  }


  /**
   * @Then /^I hit return on "([^"]*)"$/
   */
   public function iHitReturn($field)
   {
      $element = $this->getSession()->getPage()->find("css", $field);
      $this->getSession()->getDriver()->keyPress($element->getXPath(), 13);
   }


  /**
   * Returns fixed step argument (with \\" replaced back to ").
   *
   * @param string $argument
   *
   * @return string
   */
  protected function fixStepArgument($argument)
  {
      return str_replace('\\"', '"', $argument);
  }

  /**
   * @BeforeSuite
   */
  public static function prepare(BeforeSuiteScope $scope)
  {
    // prepare system for test suite
    // before it runs
  }

  /**
   * @BeforeStep
   */
  public function beforeStep()
  {
   $this->getSession()->resizeWindow(1440, 900, 'current');
  }

  /**
    * @AfterScenario @database
    */
  public function cleanDB(AfterScenarioScope $scope)
   {
       // clean database after scenarios,
       // tagged with @database
        //db_delete('users')->condition('mail', $this->email['address'])->execute();
      $usernames = array(
        $this->users,
      );

      // Check if this needs to be cleaned up.
      $scenario = $scope->getScenario();
      if (!$scenario->hasTag('database')) {
        return;
      }

      // Need to use the api driver
      $driver = $this->getDrupalParameter('api_driver');
      if (!$driver) {
        return;
      }
      if ('drupal' !== $driver) {
        throw new \Exception('Must use the Drupal driver to clean up these scenarios.');
      }

      // Bootstrap driver in case this is blackbox.
      $this->getDriver($driver)->bootstrap();

      // Change to doc root for odd loading issues.
      $current_path = getcwd();
      chdir(DRUPAL_ROOT);

      $batch = FALSE;
      foreach ($usernames as $email) {
       // if ($user = user_load_by_mail($email)) {
       //   $this->getDriver($driver)->userDelete($user);
       //   $batch = TRUE;
       // }
      }

      if ($batch) {
        $this->getDriver($driver)->processBatch();
      }

      // Change back.
      chdir($current_path);

   }

}
