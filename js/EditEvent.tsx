import React from "react";

export default function EditEvent({
	id,
	sig,
	timestamp,
	schedule,
	nonce,
	type,
	protectedHook,
	args,
}) {
	return (
		<form method="post" action="tools.php?page=wp-crontrol" className={`crontrol-edit-event crontrol-edit-event-${type}`}>
			<input type="hidden" id="_wpnonce" name="_wpnonce" value={ nonce } />
			<input name="crontrol_original_hookname" type="hidden" value={ id } />
			<input name="crontrol_original_sig" type="hidden" value={ sig } />
			<input name="crontrol_original_next_run_utc" type="hidden" value={ timestamp } />
			<input type="hidden" name="crontrol_action" value="edit_cron"/>
			<table className="form-table">
				<tbody>
					<tr className="crontrol-event-standard">
						<th scope="row">
							Hook Name
						</th>
						<td>
							{ protectedHook ? (
								<p>
									<input type="hidden" name="crontrol_hookname" value="wp_privacy_delete_old_export_files"/>
									{ id }
								</p>
							) : (
								<input type="text" autoCorrect="off" autoCapitalize="off" spellCheck="false" className="regular-text" id="crontrol_hookname" name="crontrol_hookname" value={ id } required/>
							) }
						</td>
					</tr>
					<tr className="crontrol-event-standard">
						<th scope="row">
							<label htmlFor="crontrol_args">
								Arguments (optional)
							</label>
						</th>
						<td>
							<input type="text" autoCorrect="off" autoCapitalize="off" spellCheck="false" className="regular-text code" id="crontrol_args" name="crontrol_args" value={ args } aria-describedby="crontrol_args_description"/>
								<p className="description" id="crontrol_args_description">
									Use a JSON encoded array, e.g. <code>[25]</code>, <code>["asdf"]</code>, or <code>["i","want",25,"cakes"]</code>
								</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label htmlFor="crontrol_next_run_date_local">
								Next Run
							</label>
						</th>
						<td>
							<input type="hidden" name="crontrol_next_run_date_local" value="custom" />
							<input type="date" autoCorrect="off" autoCapitalize="off" spellCheck="false" name="crontrol_next_run_date_local_custom_date" id="crontrol_next_run_date_local_custom_date" value="2025-03-06" placeholder="yyyy-mm-dd" pattern="\d{4}-\d{2}-\d{2}" />
							<input type="time" autoCorrect="off" autoCapitalize="off" spellCheck="false" name="crontrol_next_run_date_local_custom_time" id="crontrol_next_run_date_local_custom_time" value="15:59:03" step="1" placeholder="hh:mm:ss" pattern="\d{2}:\d{2}:\d{2}" />

							<p className="description">
								Timezone: UTC
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label htmlFor="crontrol_schedule">
								Schedule
							</label>
						</th>
						<td>
							<select className="postform" name="crontrol_schedule" id="crontrol_schedule" required defaultValue={ schedule }>
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
				</tbody>
			</table>
			<p className="submit">
				<input type="submit" className="button button-primary" value="Update Event"/>
			</p>
		</form>
	);
}
