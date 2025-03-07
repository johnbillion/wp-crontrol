import React from "react";
import { __ } from "@wordpress/i18n";

export default function Arguments({
	args,
}) {
	return (
		<tr>
			<th scope="row">
				<label htmlFor="crontrol_args">
					{ __( 'Arguments', 'wp-crontrol' ) }
				</label>
			</th>
			<td>
				<input
					aria-describedby="crontrol_args_description"
					autoCapitalize="off"
					autoCorrect="off"
					className="regular-text code"
					defaultValue={ args }
					id="crontrol_args"
					name="crontrol_args"
					spellCheck="false"
					type="text"
				/>
				<p className="description" id="crontrol_args_description">
					Optional. Use a JSON encoded array, e.g. <code>[25]</code>, <code>["asdf"]</code>, or <code>["i","want",25,"cakes"]</code>
				</p>
			</td>
		</tr>
	);
}
