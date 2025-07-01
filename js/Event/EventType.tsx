import React from "react";
import { __ } from "@wordpress/i18n";

interface EventTypeProps {
	type: string;
	onChange: (type: string) => void;
	canManagePHP: boolean;
}

export default function EventType({ type, onChange, canManagePHP }: EventTypeProps) {
	const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		onChange(event.target.value);
	};

	return (
		<tr>
			<th scope="row">
				{__( 'Event Type', 'wp-crontrol' )}
			</th>
			<td>
				<fieldset>
					<legend className="screen-reader-text">
						{__( 'Event Type', 'wp-crontrol' )}
					</legend>
					<p>
						<label>
							<input
								type="radio"
								name="crontrol_event_type"
								value="standard"
								checked={type === 'standard'}
								onChange={handleChange}
							/>
							{__( 'Standard cron event', 'wp-crontrol' )}
						</label>
					</p>
					<p>
						<label>
							<input
								type="radio"
								name="crontrol_event_type"
								value="url"
								checked={type === 'url'}
								onChange={handleChange}
							/>
							{__( 'URL cron event', 'wp-crontrol' )}
						</label>
					</p>
					{canManagePHP && (
						<p>
							<label>
								<input
									type="radio"
									name="crontrol_event_type"
									value="php"
									checked={type === 'php'}
									onChange={handleChange}
								/>
								{__( 'PHP cron event', 'wp-crontrol' )}
							</label>
						</p>
					)}
				</fieldset>
			</td>
		</tr>
	);
}