import React from "react";
import { __ } from "@wordpress/i18n";

export default function Schedule({
	schedule,
}) {
	return (
		<tr>
			<th scope="row">
				<label htmlFor="crontrol_edit_schedule">
					{ __( 'Schedule', 'wp-crontrol' ) }
				</label>
			</th>
			<td>
				<select className="postform" name="crontrol_schedule" id="crontrol_edit_schedule" required defaultValue={ schedule }>
					<option value="_oneoff">Non-repeating</option>
					<option value="every_minute">
						Every minute (every_minute)
					</option>
					<option value="hourly">
						Once Hourly (hourly)
					</option>
					<option value="twicedaily">
						Twice Daily (twicedaily)
					</option>
					<option value="daily">
						Once Daily (daily)
					</option>
					<option value="weekly">
						Once Weekly (weekly)
					</option>
				</select>
			</td>
		</tr>
	);
}
