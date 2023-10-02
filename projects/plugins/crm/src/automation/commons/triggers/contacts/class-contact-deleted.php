<?php
/**
 * Jetpack CRM Automation Contact_Deleted trigger.
 *
 * @package automattic/jetpack-crm
 * @since $$next-version$$
 */

namespace Automattic\Jetpack\CRM\Automation\Triggers;

use Automattic\Jetpack\CRM\Automation\Automation_Workflow;
use Automattic\Jetpack\CRM\Automation\Base_Trigger;
use Automattic\Jetpack\CRM\Automation\Data_Types\Contact_Data;

/**
 * Adds the Contact_Deleted class.
 *
 * @since $$next-version$$
 */
class Contact_Deleted extends Base_Trigger {

	/**
	 * The Automation workflow object.
	 *
	 * @since $$next-version$$
	 * @var Automation_Workflow
	 */
	protected $workflow;

	/**
	 * Get the slug name of the trigger.
	 *
	 * @since $$next-version$$
	 *
	 * @return string The slug name of the trigger.
	 */
	public static function get_slug(): string {
		return 'jpcrm/contact_delete';
	}

	/**
	 * Get the title of the trigger.
	 *
	 * @since $$next-version$$
	 *
	 * @return string|null The title of the trigger.
	 */
	public static function get_title(): ?string {
		return __( 'Contact Deleted', 'zero-bs-crm' );
	}

	/**
	 * Get the description of the trigger.
	 *
	 * @since $$next-version$$
	 *
	 * @return string|null The description of the trigger.
	 */
	public static function get_description(): ?string {
		return __( 'Triggered when a CRM contact is deleted', 'zero-bs-crm' );
	}

	/**
	 * Get the category of the trigger.
	 *
	 * @since $$next-version$$
	 *
	 * @return string|null
	 */
	public static function get_category(): ?string {
		return __( 'Contact', 'zero-bs-crm' );
	}

	/**
	 * Get the date type.
	 *
	 * @return string The type of the step
	 */
	public static function get_data_type(): string {
		return Contact_Data::class;
	}

	/**
	 * Listen to the desired event.
	 *
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	protected function listen_to_event(): void {
		$this->listen_to_wp_action( 'jpcrm_contact_deleted' );
	}
}
