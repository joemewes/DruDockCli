<?php
/**
 * @file
 * Provide Behat step-definitions for generic WetKit tests.
 *
 */

use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Behat\Behat\Context\Context;
use Drupal\Component\Utility\Random;
use Behat\Behat\Event\StepEvent;

class MetatagSubContext implements Behat\Behat\Context\Context {
//  /**
//   * Initializes context.
//   */
//  public function __construct(array $parameters = array()) {
//  }

  public static function getAlias() {
    return 'metatag';
  }

  /**
   * Get the session from the parent context.
   */
  protected function getSession() {
    return $this->getMainContext()->getSession();
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
}
