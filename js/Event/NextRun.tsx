import React from "react";
import { __, sprintf } from "@wordpress/i18n";

export default function NextRun({
	date,
	time,
}) {
	return (
		<tr>
			<th scope="row">
				<label htmlFor="crontrol_next_run_date_local_custom_date">
					{ __( 'Next Run', 'wp-crontrol' ) }
				</label>
			</th>
			<td>
				<input
					autoCapitalize="off"
					autoCorrect="off"
					id="crontrol_next_run_date_local_custom_date"
					name="crontrol_next_run_date_local_custom_date"
					pattern="\d{4}-\d{2}-\d{2}"
					placeholder="yyyy-mm-dd"
					spellCheck="false"
					type="date"
					defaultValue={ date }
				/>
				<input
					autoCapitalize="off"
					autoCorrect="off"
					id="crontrol_next_run_date_local_custom_time"
					name="crontrol_next_run_date_local_custom_time"
					pattern="\d{2}:\d{2}:\d{2}"
					placeholder="hh:mm:ss"
					spellCheck="false"
					step="1"
					type="time"
					defaultValue={ time }
				/>
				<p className="description">
					{ sprintf(
						/* translators: %s Timezone name. */
						__( 'Timezone: %s', 'wp-crontrol' ),
						window.wpCrontrol.timezone
					) }
				</p>
			</td>
		</tr>
	);
}
