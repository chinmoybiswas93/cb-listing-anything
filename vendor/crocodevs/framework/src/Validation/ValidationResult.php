<?php

namespace CrocoDevs\Validation;

/**
 * Holds the outcome of a validation pass.
 */
class ValidationResult {

	/**
	 * @var array<string, string[]> Field → error messages.
	 */
	protected $errors;

	/**
	 * @var array Validated (sanitized) data for fields that passed.
	 */
	protected $validated;

	public function __construct( array $errors, array $validated ) {
		$this->errors    = $errors;
		$this->validated = $validated;
	}

	/**
	 * Whether any validation rule failed.
	 *
	 * @return bool
	 */
	public function fails() {
		return ! empty( $this->errors );
	}

	/**
	 * Whether all rules passed.
	 *
	 * @return bool
	 */
	public function passes() {
		return empty( $this->errors );
	}

	/**
	 * Get all error messages grouped by field.
	 *
	 * @return array<string, string[]>
	 */
	public function errors() {
		return $this->errors;
	}

	/**
	 * Get the first error message for a given field, or null.
	 *
	 * @param string $field
	 *
	 * @return string|null
	 */
	public function first( $field ) {
		return isset( $this->errors[ $field ][0] ) ? $this->errors[ $field ][0] : null;
	}

	/**
	 * Get validated data (only fields that had rules and passed).
	 *
	 * @return array
	 */
	public function validated() {
		return $this->validated;
	}
}
