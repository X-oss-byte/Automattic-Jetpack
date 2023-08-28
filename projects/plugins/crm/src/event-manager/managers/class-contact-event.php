<?php
/**
 * Contact Event.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Event_Manager;

use Automattic\Jetpack\CRM\Entities\Contact;

/**
 * Contact Event class.
 *
 * @since $$next-version$$
 */
class Contact_Event implements Event {

	/**
	 * The Contact_Event instance.
	 *
	 * @since $$next-version$$
	 * @var Contact_Event
	 */
	private static $instance = null;

	/**
	 * Properties that should not be notified.
	 *
	 * @since $$next-version$$
	 * @var string[]
	 */
	private $not_notifiable_props = array(
		'created',
		'lastupdated',
		'lastcontacted',
	);

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since $$next-version$$
	 *
	 * @return Contact_Event The Contact_Event instance.
	 */
	public static function get_instance(): Contact_Event {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * A new contact was created.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $contact_data The created contact data.
	 * @return void
	 */
	public function created( array $contact_data ): void {
		do_action( 'jpcrm_contact_created', $contact_data );
	}

	/**
	 * The contact was updated.
	 *
	 * @since $$next-version$$
	 *
	 * @param Contact $contact_data The updated contact data.
	 * @param Contact $old_contact_data The old contact data.
	 * @return void
	 */
	public function updated( Contact $contact_data, Contact $old_contact_data ): void {

		// Note: Custom fields are not present in $dataArr. It's handled by addUpdateCustomField.

		// Skip social fields: tw, fb, li. They are handled/stored by the Metabox process
		// Skip lastupdate to avoid intempestive updates
		$fields_to_skip = array( 'tw', 'fb', 'li', 'lastupdated', 'wpid' );

		$contact_data_arr     = $contact_data->get_contact_array();
		$old_contact_data_arr = $old_contact_data->get_contact_array();

		// Check for effective fields changes
		$has_update = false;
		foreach ( $contact_data_arr as $field => $value ) {

			if ( in_array( $field, $fields_to_skip, true ) ) {
				continue;
			}

			if ( $value != $old_contact_data_arr[ $field ] ) { // phpcs:ignore
				// Notify only for notifiable fields
				if ( ! in_array( $field, $this->not_notifiable_props, true ) ) {
					$has_update = true;
					do_action( 'jpcrm_contact_' . $field . '_updated', $contact_data, $old_contact_data->{$field} );
				}
			}
		}

		if ( $has_update ) {
			// General notification that contact was updated
			do_action( 'jpcrm_contact_updated', $contact_data, $old_contact_data );
		}
	}

	/**
	 * A contact was deleted.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $contact_id The contact ID.
	 * @return void
	 */
	public function deleted( int $contact_id ): void {
		do_action( 'jpcrm_contact_deleted', $contact_id );
	}

	/**
	 * A contact is about to be deleted.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $contact_id The contact ID.
	 * @return void
	 */
	public function before_delete( int $contact_id ): void {
		do_action( 'jpcrm_contact_before_delete', $contact_id );
	}
}
