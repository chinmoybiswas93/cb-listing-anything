<?php

namespace CrocoDevs\Validation;

/**
 * Lightweight data validator.
 *
 * Rules are specified as pipe-delimited strings, e.g.:
 *   'title' => 'required|string|max:200'
 *
 * Supported rules:
 *   required, nullable, string, email, url, numeric, integer,
 *   boolean, array, min:<n>, max:<n>, in:<val1>,<val2>,...
 */
class Validator {

	/**
	 * Validate data against a set of rules.
	 *
	 * @param array $data  Associative array of input data.
	 * @param array $rules Associative array of field → rule string.
	 *
	 * @return ValidationResult
	 */
	public static function make( array $data, array $rules ) {
		$errors    = array();
		$validated = array();

		foreach ( $rules as $field => $ruleString ) {
			$fieldRules = is_array( $ruleString ) ? $ruleString : explode( '|', $ruleString );
			$value      = isset( $data[ $field ] ) ? $data[ $field ] : null;
			$isNullable = in_array( 'nullable', $fieldRules, true );
			$isRequired = in_array( 'required', $fieldRules, true );

			if ( $isNullable && self::isEmpty( $value ) ) {
				$validated[ $field ] = $value;
				continue;
			}

			if ( $isRequired && self::isEmpty( $value ) ) {
				$errors[ $field ][] = self::message( $field, 'required' );
				continue;
			}

			if ( ! $isRequired && self::isEmpty( $value ) ) {
				$validated[ $field ] = $value;
				continue;
			}

			$fieldPassed = true;

			foreach ( $fieldRules as $rule ) {
				if ( in_array( $rule, array( 'required', 'nullable' ), true ) ) {
					continue;
				}

				$param = null;

				if ( false !== strpos( $rule, ':' ) ) {
					list( $rule, $param ) = explode( ':', $rule, 2 );
				}

				$method = 'validate' . ucfirst( $rule );

				if ( ! method_exists( self::class, $method ) ) {
					continue;
				}

				if ( ! self::$method( $value, $param ) ) {
					$errors[ $field ][] = self::message( $field, $rule, $param );
					$fieldPassed        = false;
				}
			}

			if ( $fieldPassed ) {
				$validated[ $field ] = $value;
			}
		}

		return new ValidationResult( $errors, $validated );
	}

	// ------------------------------------------------------------------
	// Rule implementations
	// ------------------------------------------------------------------

	protected static function validateString( $value ) {
		return is_string( $value );
	}

	protected static function validateEmail( $value ) {
		return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
	}

	protected static function validateUrl( $value ) {
		return false !== filter_var( $value, FILTER_VALIDATE_URL );
	}

	protected static function validateNumeric( $value ) {
		return is_numeric( $value );
	}

	protected static function validateInteger( $value ) {
		return false !== filter_var( $value, FILTER_VALIDATE_INT );
	}

	protected static function validateBoolean( $value ) {
		return is_bool( $value )
			|| in_array( $value, array( 0, 1, '0', '1', 'true', 'false' ), true );
	}

	protected static function validateArray( $value ) {
		return is_array( $value );
	}

	/**
	 * min:<n> — checks string length or numeric value.
	 */
	protected static function validateMin( $value, $param ) {
		$min = (float) $param;

		if ( is_numeric( $value ) ) {
			return (float) $value >= $min;
		}

		return mb_strlen( (string) $value ) >= $min;
	}

	/**
	 * max:<n> — checks string length or numeric value.
	 */
	protected static function validateMax( $value, $param ) {
		$max = (float) $param;

		if ( is_numeric( $value ) ) {
			return (float) $value <= $max;
		}

		return mb_strlen( (string) $value ) <= $max;
	}

	/**
	 * in:<val1>,<val2>,... — value must be one of the listed options.
	 */
	protected static function validateIn( $value, $param ) {
		$options = explode( ',', $param );

		return in_array( (string) $value, $options, true );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	protected static function isEmpty( $value ) {
		return null === $value || '' === $value || ( is_array( $value ) && empty( $value ) );
	}

	protected static function message( $field, $rule, $param = null ) {
		$label = str_replace( '_', ' ', $field );

		$messages = array(
			'required' => sprintf( 'The %s field is required.', $label ),
			'string'   => sprintf( 'The %s must be a string.', $label ),
			'email'    => sprintf( 'The %s must be a valid email address.', $label ),
			'url'      => sprintf( 'The %s must be a valid URL.', $label ),
			'numeric'  => sprintf( 'The %s must be numeric.', $label ),
			'integer'  => sprintf( 'The %s must be an integer.', $label ),
			'boolean'  => sprintf( 'The %s must be true or false.', $label ),
			'array'    => sprintf( 'The %s must be an array.', $label ),
			'min'      => sprintf( 'The %s must be at least %s.', $label, $param ),
			'max'      => sprintf( 'The %s must not exceed %s.', $label, $param ),
			'in'       => sprintf( 'The selected %s is invalid.', $label ),
		);

		return isset( $messages[ $rule ] ) ? $messages[ $rule ] : sprintf( 'The %s is invalid.', $label );
	}
}
