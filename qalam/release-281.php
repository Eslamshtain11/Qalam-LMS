<?php
/**
 * Qalam LMS 0.28.1 — production baseline closure.
 *
 * Removes QA-only runtime artifacts left by staging builds while preserving all
 * production roles, settings, content, branding, and customer data.
 */
defined( 'ABSPATH' ) || exit;

const QALAM_281_VERSION = '0.28.1';
const QALAM_281_SCHEMA_OPTION = 'qalam_281_schema';
const QALAM_281_SCHEMA_VALUE = '1';

function qalam_281_cleanup_qa_artifacts_once(): void {
    if ( get_option( QALAM_281_SCHEMA_OPTION ) === QALAM_281_SCHEMA_VALUE ) {
        return;
    }

    delete_option( 'qalam_270_runtime_report_v1' );
    delete_option( 'qalam_270_runtime_probe_value' );
    delete_transient( 'qalam_270_runtime_probe_lock' );

    $runtime_dir = __DIR__ . '/runtime';
    if ( is_dir( $runtime_dir ) ) {
        foreach ( glob( $runtime_dir . '/qalam-*-runtime-report.json' ) ?: array() as $report_file ) {
            if ( is_file( $report_file ) && ! is_link( $report_file ) ) {
                @unlink( $report_file );
            }
        }
        $remaining = array_diff( scandir( $runtime_dir ) ?: array(), array( '.', '..' ) );
        if ( empty( $remaining ) ) {
            @rmdir( $runtime_dir );
        }
    }

    update_option( QALAM_281_SCHEMA_OPTION, QALAM_281_SCHEMA_VALUE, false );
}
add_action( 'init', 'qalam_281_cleanup_qa_artifacts_once', 10 );
