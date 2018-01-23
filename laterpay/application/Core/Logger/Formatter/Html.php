<?php

namespace LaterPay\Core\Logger\Formatter;

/**
 * LaterPay logger HTML formatter.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Html extends Normalizer {

	/**
	 * Format a set of log records.
	 *
	 * @param array $records A set of records to format
	 *
	 * @return mixed The formatted set of records
	 */
	public function formatBatch( array $records ) {
		$message = '';
		foreach ( $records as $record ) {
			$message .= $this->format( $record );
		}

		return $message;
	}

	/**
	 * Format a log record.
	 *
	 * @param array $record A record to format
	 *
	 * @return mixed The formatted record
	 */
	public function format( array $record ) {
		$output  = '<li class="lp_debugger-content-list__item">';
		$output .= '<table class="lp_js_debuggerContentTable lp_debugger-content__table lp_is-hidden">';

		// generate thead of log record
		$output .= $this->addHeadRow( (string) $record['message'], $record['level'] );

		// generate tbody of log record with details
		$output .= '<tbody class="lp_js_logEntryDetails lp_debugger-content__table-body" style="display:none;">';
		$output .= '<tr><td class="lp_debugger-content__table-td" colspan="2"><table class="lp_debugger-content__table">';

		if ( $record['context'] ) {
			foreach ( $record['context'] as $key => $value ) {
				$output .= $this->addRow( $key, $this->convertToString( $value ) );
			}
		}

		if ( $record['extra'] ) {
			foreach ( $record['extra'] as $key => $value ) {
				$output .= $this->addRow( $key, $this->convertToString( $value ) );
			}
		}

		$output .= '</td></tr></table>';
		$output .= '</tbody>';
		$output .= '</table>';
		$output .= '</li>';

		return $output;
	}

	/**
	 * Create the header row for a log record.
	 *
	 * @param string $message log message
	 * @param int $level log level
	 *
	 * @return string
	 */
	private function addHeadRow( $message = '', $level ) {
		$show_details_link = '<a href="#" class="lp_js_toggleLogDetails" data-icon="l">' . esc_html(
			__(
				'Details',
				'laterpay'
			)
		) . '</a>';

		$html = '<thead class="lp_js_debuggerContentTableTitle lp_debugger-content__table-title">
            <tr>
                <td class="lp_debugger-content__table-td"><span class="lp_debugger__log-level lp_debugger__log-level--' . esc_attr( $level ) . ' lp_vectorIcon"></span>' . esc_html( $message ) . '</td>
                <td class="lp_debugger-content__table-td">' . wp_kses_post( $show_details_link ) . '</td>
            </tr>
        </thead>';

		return $html;
	}

	/**
	 * Create an HTML table row.
	 *
	 * @param  string $th Row header content
	 * @param  string $td Row standard cell content
	 * @param  bool $escapeTd false if td content must not be HTML escaped
	 *
	 * @return string
	 */
	private function addRow( $th, $td = ' ', $escapeTd = true ) {
		$th = htmlspecialchars( $th, ENT_NOQUOTES );

		if ( $escapeTd ) {
			$td = htmlspecialchars( $td, ENT_NOQUOTES );
		}

		$html = '<tr>
                    <th class="lp_debugger-content__table-th" title="' . esc_attr( $th ) . '">' . $th . '</th>
                    <td class="lp_debugger-content__table-td">' . $td . '</td>
                </tr>';

		return $html;
	}

	/**
	 * Convert data into string
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	protected function convertToString( $data ) {
		if ( null === $data || is_scalar( $data ) ) {
			return (string) $data;
		}

		$data = $this->normalize( $data );
		if ( PHP_VERSION_ID >= 50400 ) {
			return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		return str_replace( '\\/', '/', wp_json_encode( $data ) );
	}
}
