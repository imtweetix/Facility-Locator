<?php

/**
 * Register additional taxonomies for the Facility Locator plugin.
 */
class Facility_Locator_Taxonomies
{

  public static function register_taxonomies()
  {
    self::register_therapy_taxonomy();
    self::register_environment_taxonomy();
    self::register_location_taxonomy();
    self::register_insurance_taxonomy();
  }

  private static function register_therapy_taxonomy()
  {
    $labels = array(
      'name'              => 'Therapies',
      'singular_name'     => 'Therapy',
      'search_items'      => 'Search Therapies',
      'all_items'         => 'All Therapies',
      'edit_item'         => 'Edit Therapy',
      'update_item'       => 'Update Therapy',
      'add_new_item'      => 'Add New Therapy',
      'new_item_name'     => 'New Therapy Name',
      'menu_name'         => 'Therapies'
    );

    register_taxonomy('facility_therapy', 'facility', array(
      'hierarchical'      => true,
      'labels'            => $labels,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array('slug' => 'therapy')
    ));
  }

  private static function register_environment_taxonomy()
  {
    $labels = array(
      'name'              => 'Environments',
      'singular_name'     => 'Environment',
      'search_items'      => 'Search Environments',
      'all_items'         => 'All Environments',
      'edit_item'         => 'Edit Environment',
      'update_item'       => 'Update Environment',
      'add_new_item'      => 'Add New Environment',
      'new_item_name'     => 'New Environment Name',
      'menu_name'         => 'Environment'
    );

    register_taxonomy('facility_environment', 'facility', array(
      'hierarchical'      => true,
      'labels'            => $labels,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array('slug' => 'environment')
    ));
  }

  private static function register_location_taxonomy()
  {
    $labels = array(
      'name'              => 'Locations',
      'singular_name'     => 'Location',
      'search_items'      => 'Search Locations',
      'all_items'         => 'All Locations',
      'edit_item'         => 'Edit Location',
      'update_item'       => 'Update Location',
      'add_new_item'      => 'Add New Location',
      'new_item_name'     => 'New Location Name',
      'menu_name'         => 'Locations'
    );

    register_taxonomy('facility_location', 'facility', array(
      'hierarchical'      => true,
      'labels'            => $labels,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array('slug' => 'location')
    ));
  }

  private static function register_insurance_taxonomy()
  {
    $labels = array(
      'name'              => 'Insurance Providers',
      'singular_name'     => 'Insurance Provider',
      'search_items'      => 'Search Providers',
      'all_items'         => 'All Providers',
      'edit_item'         => 'Edit Provider',
      'update_item'       => 'Update Provider',
      'add_new_item'      => 'Add New Provider',
      'new_item_name'     => 'New Provider Name',
      'menu_name'         => 'Insurance Providers'
    );

    register_taxonomy('facility_insurance', 'facility', array(
      'hierarchical'      => true,
      'labels'            => $labels,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array('slug' => 'insurance')
    ));
  }
}

// Hook into init
add_action('init', array('Facility_Locator_Taxonomies', 'register_taxonomies'));
