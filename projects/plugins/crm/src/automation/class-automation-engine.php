<?php

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Automation Engine
 *
 * @package Automattic\Jetpack\CRM\Automation
 */
class Automation_Engine {

	/** @var Automation_Engine Instance singleton */
	private static $instance = null;

	/** @var array triggers map name => classname */
	private $triggers_map = array();
	
	/** @var array steps map name => classname */
	private $steps_map = array();

	/** @var Automation_Logger Automation logger */
	private $automation_logger;

	/** @var array */
	private $workflows = array();

	/**
	 *  Instance singleton object
	 *
	 * @return Automation_Engine
	 */
	public static function instance(): Automation_Engine {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set the automation logger
	 *
	 * @param Automation_Logger $logger The automation logger.
	 */
	public function set_automation_logger( Automation_Logger $logger ) {
		$this->automation_logger = $logger;
	}

	/**
	 * Register a trigger
	 *
	 * @param string Trigger $trigger_name
	 * @param string         $class_name
	 * @throws Automation_Exception
	 */
	public function register_trigger( string $trigger_name, string $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			throw new Automation_Exception(
				sprintf( __( 'Trigger class %s does not exist', 'zero-bs-crm' ), $class_name ),
				Automation_Exception::TRIGGER_CLASS_NOT_FOUND
			);
		}
		$this->triggers_map[ $trigger_name ] = $class_name;
	}

	/**
	 * Register a step in the automation engine
	 * 
	 * @param string $step_name
	 * @param string $class_name
	 * @throws Automation_Exception
	 */
	public function register_step( string $step_name, string $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			throw new Automation_Exception( sprintf( __( 'Step class %s does not exist', 'zero-bs-crm' ), $class_name ), Automation_Exception::STEP_CLASS_NOT_FOUND );
		}
		$this->steps_map[ $step_name ] = $class_name;
	}

	/**
	 * Get a step class by name
	 *
	 * @param string $step_name
	 * @return string
	 * @throws Automation_Exception
	 */
	public function get_step_class( string $step_name ): string {
		if ( ! isset( $this->steps_map[ $step_name ] ) ) {
			throw new Automation_Exception( sprintf( __( 'Step %s does not exist', 'zero-bs-crm' ), $step_name ), Automation_Exception::STEP_CLASS_NOT_FOUND );
		}
		return $this->steps_map[ $step_name ];
	}

	/**
	 * Add a workflow
	 *
	 * @param Automation_Workflow $workflow
	 * @param bool              $init_workflow
	 * @return void
	 * @throws Workflow_Exception
	 */
	public function add_workflow( Automation_Workflow $workflow, bool $init_workflow = false ) {
		$this->workflows[] = $workflow;

		if ( $init_workflow ) {
			$workflow->init_triggers();
		}
	}

	/**
	 * Build and add a workflow
	 *
	 * @param array $workflow_data
	 * @param bool $init_workflow
	 * @return Automation_Workflow
	 * @throws Workflow_Exception
	 */
	public function build_add_workflow( array $workflow_data, bool $init_workflow = false ): Automation_Workflow {
		$workflow = new Automation_Workflow( $workflow_data );
		$this->add_workflow( $workflow, $init_workflow );

		return $workflow;
	}

	/**
	 * Init automation workflows.
	 *
	 * @return void
	 * @throws Workflow_Exception
	 */
	public function init_workflows() {
		
		/** @var Automation_Workflow $workflow */
		foreach ( $this->workflows as $workflow ) {
			$workflow->init_triggers();
		}
	}

	/**
	 * Get step instance
	 * 
	 * @param $step_name
	 * @param array $step_data
	 * @return Step
	 * @throws Automation_Exception
	 */
	public function get_registered_step( $step_name, array $step_data = array() ): Step {
		
		$step_class = $this->get_step_class( $step_name );
		
		if ( ! class_exists( $step_class ) ) {
			throw new Automation_Exception( sprintf( __( 'Step class %s does not exist', 'zero-bs-crm' ), $step_class ), Automation_Exception::STEP_CLASS_NOT_FOUND );
		}
		
		return new $step_class( $step_data );
	}

	/**
	 * Get registered steps
	 * 
	 * @return array
	 */
	public function get_registered_steps():array {
		return $this->steps_map;
	}

	/**
	 * Get trigger instance
	 *
	 * @param string $trigger_name
	 * @return string
	 * @throws Automation_Exception
	 */
	public function get_trigger_class( string $trigger_name ): string {
		
		if ( ! isset( $this->triggers_map[ $trigger_name ] ) ) {
			throw new Automation_Exception( sprintf( __( 'Trigger %s does not exist', 'zero-bs-crm' ), $trigger_name ), Automation_Exception::TRIGGER_CLASS_NOT_FOUND );
		}
		
		return $this->triggers_map[ $trigger_name ];
	}

	/**
	 * Get Automation logger
	 * 
	 * @return Automation_Logger
	 */
	public function get_logger(): ?Automation_Logger {
		return $this->automation_logger;
	}
}
