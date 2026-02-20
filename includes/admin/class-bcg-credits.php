<?php
/**
 * Credits handler.
 *
 * Manages the credits system including balance queries, credit additions,
 * deductions, refunds, transaction logging, and the AJAX endpoints for
 * Stripe payment processing.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Credits
 *
 * Handles credit balance management, top-up via Stripe, and transaction logging.
 *
 * @since 1.0.0
 */
class BCG_Credits {

	/**
	 * The credits database table name (without prefix).
	 *
	 * @var string
	 */
	const CREDITS_TABLE = 'bcg_credits';

	/**
	 * The transactions database table name (without prefix).
	 *
	 * @var string
	 */
	const TRANSACTIONS_TABLE = 'bcg_transactions';

	/**
	 * Constructor.
	 *
	 * Registers AJAX handlers for Stripe payment flow and credit operations.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_bcg_stripe_create_intent', array( $this, 'handle_stripe_create_intent' ) );
		add_action( 'wp_ajax_bcg_stripe_confirm', array( $this, 'handle_stripe_confirm' ) );
		add_action( 'wp_ajax_bcg_get_credit_balance', array( $this, 'handle_get_credit_balance' ) );
	}

	/**
	 * Ensure a credit record exists for the given user.
	 *
	 * If no row exists in the bcg_credits table for this user, one is created
	 * with a zero balance. This method is safe to call multiple times.
	 *
	 * @since  1.0.0
	 * @param  int $user_id The WordPress user ID.
	 * @return void
	 */
	public function ensure_user_record( int $user_id ): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::CREDITS_TABLE;
		$exists  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);

		if ( ! $exists ) {
			$wpdb->insert(
				$table,
				array(
					'user_id'    => $user_id,
					'balance'    => 0.0000,
					'updated_at' => current_time( 'mysql' ),
				),
				array( '%d', '%f', '%s' )
			);
		}
	}

	/**
	 * Get the current credit balance for a user.
	 *
	 * @since  1.0.0
	 * @param  int $user_id The WordPress user ID.
	 * @return float The current credit balance.
	 */
	public function get_balance( int $user_id ): float {
		global $wpdb;

		$table   = $wpdb->prefix . self::CREDITS_TABLE;
		$balance = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT balance FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);

		return $balance !== null ? (float) $balance : 0.0;
	}

	/**
	 * Add credits to a user's balance and log the transaction.
	 *
	 * Used for top-ups via Stripe. Creates the user credit record if it
	 * does not yet exist.
	 *
	 * @since  1.0.0
	 * @param  int         $user_id     The WordPress user ID.
	 * @param  float       $amount      The number of credits to add (positive).
	 * @param  string      $description A human-readable description of the top-up.
	 * @param  string|null $stripe_pi   The Stripe PaymentIntent ID, if applicable.
	 * @return float|WP_Error The new balance on success, or WP_Error on failure.
	 */
	public function add_credits( int $user_id, float $amount, string $description = '', ?string $stripe_pi = null ): float|\WP_Error {
		global $wpdb;

		if ( $amount <= 0 ) {
			return new \WP_Error(
				'invalid_amount',
				__( 'Credit amount must be greater than zero.', 'brevo-campaign-generator' )
			);
		}

		$this->ensure_user_record( $user_id );

		$credits_table      = $wpdb->prefix . self::CREDITS_TABLE;
		$transactions_table = $wpdb->prefix . self::TRANSACTIONS_TABLE;

		// Update the balance atomically.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$credits_table} SET balance = balance + %f, updated_at = %s WHERE user_id = %d",
				$amount,
				current_time( 'mysql' ),
				$user_id
			)
		);

		if ( false === $updated ) {
			return new \WP_Error(
				'db_error',
				__( 'Failed to update credit balance.', 'brevo-campaign-generator' )
			);
		}

		// Get the new balance.
		$new_balance = $this->get_balance( $user_id );

		// Log the transaction.
		$wpdb->insert(
			$transactions_table,
			array(
				'user_id'              => $user_id,
				'type'                 => 'topup',
				'amount'               => $amount,
				'balance_after'        => $new_balance,
				'description'          => sanitize_text_field( $description ),
				'stripe_payment_intent' => $stripe_pi ? sanitize_text_field( $stripe_pi ) : null,
				'ai_service'           => null,
				'ai_task'              => null,
				'tokens_used'          => null,
				'created_at'           => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $new_balance;
	}

	/**
	 * Deduct credits from a user's balance and log the transaction.
	 *
	 * Used when an AI generation task is performed. Checks that the user
	 * has sufficient credits before deducting.
	 *
	 * @since  1.0.0
	 * @param  int         $user_id The WordPress user ID.
	 * @param  float       $amount  The number of credits to deduct (positive).
	 * @param  string|null $service The AI service used (e.g. 'openai', 'gemini-pro', 'gemini-flash').
	 * @param  string|null $task    The AI task identifier (e.g. 'generate_headline').
	 * @param  int|null    $tokens  The number of tokens used, if applicable.
	 * @return float|WP_Error The new balance on success, or WP_Error on failure.
	 */
	public function deduct_credits( int $user_id, float $amount, ?string $service = null, ?string $task = null, ?int $tokens = null ): float|\WP_Error {
		global $wpdb;

		if ( $amount <= 0 ) {
			return new \WP_Error(
				'invalid_amount',
				__( 'Deduction amount must be greater than zero.', 'brevo-campaign-generator' )
			);
		}

		$this->ensure_user_record( $user_id );

		$current_balance = $this->get_balance( $user_id );

		if ( $current_balance < $amount ) {
			return new \WP_Error(
				'insufficient_credits',
				sprintf(
					/* translators: 1: required credits, 2: current balance */
					__( 'Insufficient credits. This operation requires %1$s credits but your balance is %2$s.', 'brevo-campaign-generator' ),
					number_format( $amount, 0 ),
					number_format( $current_balance, 0 )
				)
			);
		}

		$credits_table      = $wpdb->prefix . self::CREDITS_TABLE;
		$transactions_table = $wpdb->prefix . self::TRANSACTIONS_TABLE;

		// Deduct atomically.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$credits_table} SET balance = balance - %f, updated_at = %s WHERE user_id = %d AND balance >= %f",
				$amount,
				current_time( 'mysql' ),
				$user_id,
				$amount
			)
		);

		if ( false === $updated || 0 === $updated ) {
			return new \WP_Error(
				'deduction_failed',
				__( 'Failed to deduct credits. Possible race condition — please try again.', 'brevo-campaign-generator' )
			);
		}

		// Get the new balance.
		$new_balance = $this->get_balance( $user_id );

		// Build a description based on the service and task.
		$description = $this->build_usage_description( $service, $task );

		// Validate the ai_service value for the ENUM column.
		$valid_services = array( 'openai', 'gemini-pro', 'gemini-flash' );
		$db_service     = ( $service && in_array( $service, $valid_services, true ) ) ? $service : null;

		// Log the transaction.
		$wpdb->insert(
			$transactions_table,
			array(
				'user_id'              => $user_id,
				'type'                 => 'usage',
				'amount'               => -$amount,
				'balance_after'        => $new_balance,
				'description'          => $description,
				'stripe_payment_intent' => null,
				'ai_service'           => $db_service,
				'ai_task'              => $task ? sanitize_text_field( $task ) : null,
				'tokens_used'          => $tokens,
				'created_at'           => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $new_balance;
	}

	/**
	 * Refund credits to a user's balance and log the transaction.
	 *
	 * Used when an AI API call fails after credits have been deducted.
	 *
	 * @since  1.0.0
	 * @param  int    $user_id     The WordPress user ID.
	 * @param  float  $amount      The number of credits to refund (positive).
	 * @param  string $description A human-readable description of the refund reason.
	 * @return float|WP_Error The new balance on success, or WP_Error on failure.
	 */
	public function refund_credits( int $user_id, float $amount, string $description = '' ): float|\WP_Error {
		global $wpdb;

		if ( $amount <= 0 ) {
			return new \WP_Error(
				'invalid_amount',
				__( 'Refund amount must be greater than zero.', 'brevo-campaign-generator' )
			);
		}

		$this->ensure_user_record( $user_id );

		$credits_table      = $wpdb->prefix . self::CREDITS_TABLE;
		$transactions_table = $wpdb->prefix . self::TRANSACTIONS_TABLE;

		// Add the refund atomically.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$credits_table} SET balance = balance + %f, updated_at = %s WHERE user_id = %d",
				$amount,
				current_time( 'mysql' ),
				$user_id
			)
		);

		if ( false === $updated ) {
			return new \WP_Error(
				'db_error',
				__( 'Failed to process credit refund.', 'brevo-campaign-generator' )
			);
		}

		// Get the new balance.
		$new_balance = $this->get_balance( $user_id );

		// Log the transaction.
		$wpdb->insert(
			$transactions_table,
			array(
				'user_id'              => $user_id,
				'type'                 => 'refund',
				'amount'               => $amount,
				'balance_after'        => $new_balance,
				'description'          => sanitize_text_field( $description ),
				'stripe_payment_intent' => null,
				'ai_service'           => null,
				'ai_task'              => null,
				'tokens_used'          => null,
				'created_at'           => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $new_balance;
	}

	/**
	 * Get a paginated list of transactions for a user.
	 *
	 * @since  1.0.0
	 * @param  int   $user_id The WordPress user ID.
	 * @param  array $args    {
	 *     Optional. Query arguments.
	 *
	 *     @type string $type     Filter by transaction type: 'all', 'topup', 'usage', 'refund'. Default 'all'.
	 *     @type int    $per_page Number of results per page. Default 20.
	 *     @type int    $page     The page number (1-based). Default 1.
	 *     @type string $orderby  Column to order by. Default 'created_at'.
	 *     @type string $order    Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 * }
	 * @return array {
	 *     @type array $items       Array of transaction objects.
	 *     @type int   $total       Total number of matching transactions.
	 *     @type int   $total_pages Total number of pages.
	 *     @type int   $page        Current page number.
	 *     @type int   $per_page    Items per page.
	 * }
	 */
	public function get_transactions( int $user_id, array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'type'     => 'all',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$table    = $wpdb->prefix . self::TRANSACTIONS_TABLE;
		$per_page = absint( $args['per_page'] );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		// Validate order direction.
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Validate orderby column.
		$valid_columns = array( 'created_at', 'amount', 'type', 'balance_after' );
		$orderby       = in_array( $args['orderby'], $valid_columns, true ) ? $args['orderby'] : 'created_at';

		// Build WHERE clause.
		$where      = "WHERE user_id = %d";
		$where_args = array( $user_id );

		$valid_types = array( 'topup', 'usage', 'refund' );
		if ( 'all' !== $args['type'] && in_array( $args['type'], $valid_types, true ) ) {
			$where       .= " AND type = %s";
			$where_args[] = $args['type'];
		}

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM {$table} {$where}";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic query built safely above.
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$where_args ) );

		// Get the items.
		$query      = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_args = array_merge( $where_args, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic query built safely above.
		$items = $wpdb->get_results( $wpdb->prepare( $query, ...$query_args ) );

		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		return array(
			'items'       => $items ? $items : array(),
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Check whether a user has sufficient credits for an operation.
	 *
	 * @since  1.0.0
	 * @param  int   $user_id The WordPress user ID.
	 * @param  float $amount  The credits required.
	 * @return bool True if the user has enough credits.
	 */
	public function has_sufficient_credits( int $user_id, float $amount ): bool {
		return $this->get_balance( $user_id ) >= $amount;
	}

	/**
	 * Build a human-readable description for a usage transaction.
	 *
	 * @since  1.0.0
	 * @param  string|null $service The AI service name.
	 * @param  string|null $task    The AI task identifier.
	 * @return string The description.
	 */
	private function build_usage_description( ?string $service, ?string $task ): string {
		$service_labels = array(
			'openai'       => 'OpenAI',
			'gemini-pro'   => 'Gemini Pro',
			'gemini-flash' => 'Gemini Flash',
		);

		$task_labels = array(
			'generate_subject_line'       => __( 'Subject line generation', 'brevo-campaign-generator' ),
			'generate_preview_text'       => __( 'Preview text generation', 'brevo-campaign-generator' ),
			'generate_main_headline'      => __( 'Main headline generation', 'brevo-campaign-generator' ),
			'generate_main_description'   => __( 'Main description generation', 'brevo-campaign-generator' ),
			'generate_product_headline'   => __( 'Product headline generation', 'brevo-campaign-generator' ),
			'generate_product_short_desc' => __( 'Product description generation', 'brevo-campaign-generator' ),
			'generate_coupon_suggestion'  => __( 'Coupon suggestion generation', 'brevo-campaign-generator' ),
			'generate_main_image'         => __( 'Main image generation', 'brevo-campaign-generator' ),
			'generate_product_image'      => __( 'Product image generation', 'brevo-campaign-generator' ),
		);

		$service_label = isset( $service_labels[ $service ] ) ? $service_labels[ $service ] : ( $service ?? __( 'AI', 'brevo-campaign-generator' ) );
		$task_label    = isset( $task_labels[ $task ] ) ? $task_labels[ $task ] : ( $task ?? __( 'generation', 'brevo-campaign-generator' ) );

		return sprintf(
			/* translators: 1: AI service name, 2: task description */
			__( '%1$s — %2$s', 'brevo-campaign-generator' ),
			$service_label,
			$task_label
		);
	}

	/**
	 * Handle the AJAX request to create a Stripe PaymentIntent.
	 *
	 * Receives a pack_key (0, 1, or 2), creates a PaymentIntent via
	 * the BCG_Stripe class, and returns the client secret.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_stripe_create_intent(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$pack_key = isset( $_POST['pack_key'] ) ? absint( $_POST['pack_key'] ) : -1;

		if ( $pack_key < 0 || $pack_key > 2 ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid credit pack selection.', 'brevo-campaign-generator' ) )
			);
		}

		$stripe = new BCG_Stripe();
		$result = $stripe->create_payment_intent( $pack_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle the AJAX request to confirm a Stripe payment and add credits.
	 *
	 * Verifies the PaymentIntent succeeded, then adds the corresponding
	 * credits to the user's balance.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_stripe_confirm(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$payment_intent_id = isset( $_POST['payment_intent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_intent_id'] ) ) : '';

		if ( empty( $payment_intent_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Missing payment intent ID.', 'brevo-campaign-generator' ) )
			);
		}

		$stripe = new BCG_Stripe();
		$result = $stripe->confirm_payment( $payment_intent_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		// Extract the credits from the metadata.
		$credits  = (float) $result['credits'];
		$user_id  = get_current_user_id();
		$currency = strtoupper( get_option( 'bcg_stripe_currency', 'GBP' ) );

		// Build a description.
		$description = sprintf(
			/* translators: 1: number of credits, 2: formatted price, 3: currency code */
			__( 'Top-up: %1$s credits purchased for %2$s %3$s via Stripe', 'brevo-campaign-generator' ),
			number_format( $credits, 0 ),
			number_format( $result['amount_paid'] / 100, 2 ),
			$currency
		);

		// Add the credits.
		$new_balance = $this->add_credits( $user_id, $credits, $description, $payment_intent_id );

		if ( is_wp_error( $new_balance ) ) {
			wp_send_json_error(
				array( 'message' => $new_balance->get_error_message() )
			);
		}

		wp_send_json_success(
			array(
				'message'     => sprintf(
					/* translators: %s: number of credits added */
					__( 'Successfully added %s credits to your balance!', 'brevo-campaign-generator' ),
					number_format( $credits, 0 )
				),
				'new_balance' => $new_balance,
				'credits_added' => $credits,
			)
		);
	}

	/**
	 * Handle the AJAX request to get the current credit balance.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_get_credit_balance(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$balance = $this->get_balance( get_current_user_id() );

		wp_send_json_success(
			array(
				'balance'           => $balance,
				'balance_formatted' => number_format( $balance, 0 ),
			)
		);
	}
}
