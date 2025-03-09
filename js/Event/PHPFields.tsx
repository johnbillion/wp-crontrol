import React from "react";
import { __, sprintf } from "@wordpress/i18n";

export default function PHPFields({
	args,
	integrityCheck,
}) {
	return (
		<>
			<tr>
				<th colSpan={2} style={{ paddingBottom: 0 }}>
					<label htmlFor="crontrol_hookcode">
						{ __( 'PHP Code', 'wp-crontrol' ) }
					</label>
				</th>
			</tr>
			<tr>
				<td colSpan={2} style={{ padding: 0 }}>
					{ integrityCheck && (
						<div className="notice notice-error inline">
							<p>
								{ __(
									'The PHP code in this event needs to be checked for integrity. This event will not run until you re-save it.',
									'wp-crontrol'
								) }
							</p>
							<p>
								<a href="https://wp-crontrol.com/help/check-cron-events/">
									{ __( 'Read what to do', 'wp-crontrol' ) }
								</a>
							</p>
						</div>
					) }
					<p className="description">
						{ sprintf(
							/* translators: The PHP tag name */
							__( 'The opening %s tag must not be included.', 'wp-crontrol' ),
							'<code>&lt;?php</code>'
						) }
					</p>
					<p>
						<textarea
							className="large-text code"
							cols={50}
							id="crontrol_hookcode"
							name="crontrol_hookcode"
							rows={10}
						>
							{ args.code ?? '' }
						</textarea>
					</p>
				</td>
			</tr>
		</>
	);
}
