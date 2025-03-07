import React from "react";
import { __ } from "@wordpress/i18n";

export default function EventName({
	name,
}) {
	return (
		<tr>
			<th scope="row">
				<label htmlFor="crontrol_eventname">
					{ __( 'Event Name (optional)', 'wp-crontrol' ) }
				</label>
			</th>
			<td>
				<input
					autoCapitalize="off"
					autoCorrect="off"
					className="regular-text"
					defaultValue={ name }
					id="crontrol_eventname"
					name="crontrol_eventname"
					spellCheck="false"
					type="text"
				/>
			</td>
		</tr>
	);
}
