import React from "react";
import { __ } from "@wordpress/i18n";

interface HookNameProps {
	name: string;
	protectedHook: boolean;
}

export default function HookName({
	name,
	protectedHook,
}: HookNameProps) {
	return (
		<tr>
			<th scope="row">
				{ protectedHook ? (
					<>
						{ __( 'Hook Name', 'wp-crontrol' ) }
					</>
				) : (
					<label htmlFor="crontrol_hookname">
						{ __( 'Hook Name', 'wp-crontrol' ) }
					</label>
				) }
			</th>
			<td>
				{ protectedHook ? (
					<p>
						<input type="hidden" name="crontrol_hookname" value={ name } />
						{ name }
					</p>
				) : (
					<input
						autoCapitalize="off"
						autoCorrect="off"
						className="regular-text"
						defaultValue={ name }
						id="crontrol_hookname"
						name="crontrol_hookname"
						required
						spellCheck="false"
						type="text"
					/>
				) }
			</td>
		</tr>
	);
}
