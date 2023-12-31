<?php

namespace Drupal\Tests\domain_alias\Functional;

use Drupal\user\RoleInterface;

/**
 * Tests behavior for the domain alias wildcard match handler.
 *
 * @group domain_alias
 */
class DomainAliasWildcardTest extends DomainAliasTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['domain', 'domain_alias', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create 3 domains. These will be example.com, one.example.com,
    // two.example.com.
    $this->domainCreateTestDomains(3);
  }

  /**
   * Test for environment matching.
   */
  public function testDomainAliasWildcards() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $alias_loader = \Drupal::entityTypeManager()->getStorage('domain_alias');
    $domains = $domain_storage->loadMultipleSorted();
    // Our patterns should map to example.com, one.example.com, two.example.com.
    $patterns = ['example.*', 'four.example.*', 'five.example.*'];
    $domain = NULL;
    foreach ($domains as $domain) {
      $values = [
        'domain_id' => $domain->id(),
        'pattern' => array_shift($patterns),
        'redirect' => 0,
        'environment' => 'local',
      ];
      $this->createDomainAlias($values);
    }
    // Test the environment loader.
    $local = $alias_loader->loadByEnvironment('local');
    $this->assertTrue(count($local) === 3, 'Three aliases set to local');
    // Test the environment matcher. $domain here is two.example.com.
    $match = $alias_loader->loadByEnvironmentMatch($domain, 'local');
    $this->assertTrue(count($match) === 1, 'One environment match loaded');
    $alias = current($match);
    $this->assertTrue($alias->getPattern() === 'five.example.*', 'Proper pattern match loaded.');

    // Test the environment matcher. $domain here is one.example.com.
    $domain = $domain_storage->load('one_example_com');
    $matches = $alias_loader->loadByEnvironmentMatch($domain, 'local');
    $this->assertTrue(count($matches) === 1, 'One environment match loaded');
    $alias = current($matches);
    $this->assertTrue($alias->getPattern() === 'four.example.*', 'Proper pattern match loaded.');

    // Now load a page and check things.
    // Since we cannot read the service request, we place a block
    // which shows links to all domains.
    $this->drupalPlaceBlock('domain_switcher_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['administer domains']);
    // For a non-aliased request, the url list should be normal.
    $this->drupalGet($domain->getPath());
    foreach ($domains as $domain) {
      $this->assertSession()->assertEscaped($domain->getHostname());
      $this->assertSession()->linkByHrefExists($domain->getPath(), 0, 'Link found: ' . $domain->getPath());
    }
    // For an aliased request (four.example.com), the list should be aliased.
    $url = $domain->getScheme() . str_replace('*', $this->baseTLD, $alias->getPattern());
    $this->drupalGet($url);
    foreach ($matches as $match) {
      $this->assertSession()->assertEscaped(str_replace('*', $this->baseTLD, $match->getPattern()));
    }
  }

}
