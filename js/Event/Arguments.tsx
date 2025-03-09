import React from "react";
import { __, sprintf } from "@wordpress/i18n";
import { createInterpolateElement } from "@wordpress/element";

interface ArgumentsProps {
	args: string;
}

export default function Arguments({
	args,
}: ArgumentsProps) {
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
					{ createInterpolateElement(
						sprintf(
							/* translators: 1, 2, and 3: Example values for an input field. */
							__( 'Use a JSON encoded array, e.g. %1$s, %2$s, or %3$s', 'wp-crontrol' ),
							'<code>[25]</code>',
							'<code>["asdf"]</code>',
							'<code>["i","want",25,"cakes"]</code>'
						),
						{
							code: <code/>,
						}
					) }
				</p>
			</td>
		</tr>
	);
}
