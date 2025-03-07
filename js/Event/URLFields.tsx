import React from "react";
import { __ } from "@wordpress/i18n";

export default function URLFields({
	args,
	integrityCheck,
}) {
	return (
		<tr>
			<th scope="row">
				<label htmlFor="crontrol_url">
					{ __( 'URL', 'wp-crontrol' ) }
				</label>
			</th>
			<td>
				{ integrityCheck && (
					<div className="notice notice-error inline">
						<p>
							{ __(
								'The URL in this event needs to be checked for integrity. This event will not run until you re-save it.',
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
				<input
					className="regular-text code"
					defaultValue={ args.url ?? '' }
					id="crontrol_url"
					name="crontrol_url"
					required
					type="url"
				/>
			</td>
		</tr>
	);
}
