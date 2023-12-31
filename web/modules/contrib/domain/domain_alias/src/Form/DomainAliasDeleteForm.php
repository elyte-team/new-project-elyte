<?php

namespace Drupal\domain_alias\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Builds the form to delete a domain_alias record.
 */
class DomainAliasDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\domain_alias\DomainAliasInterface $alias */
    $alias = $this->entity;
    return new Url('domain_alias.admin', [
      'domain' => $alias->getDomainId(),
    ]);
  }

}
