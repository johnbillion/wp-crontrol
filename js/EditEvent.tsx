import React from "react";
import { __ } from "@wordpress/i18n";

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
			<input type="hidden" name="_wpnonce" value={ nonce } />
			<input type="hidden" name="crontrol_original_hookname" value={ id } />
			<input type="hidden" name="crontrol_original_sig" value={ sig } />
			<input type="hidden" name="crontrol_original_next_run_utc" value={ timestamp } />
			<input type="hidden" name="crontrol_action" value="edit_cron" />
			<table className="form-table">
				<tr className="crontrol-event-standard">
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
								<input type="hidden" name="crontrol_hookname" value={ id } />
								{ id }
							</p>
						) : (
							<input type="text" autoCorrect="off" autoCapitalize="off" spellCheck="false" className="regular-text" id="crontrol_hookname" name="crontrol_hookname" defaultValue={ id } required />
						) }
					</td>
				</tr>
				<tr className="crontrol-event-standard">
					<th scope="row">
						<label htmlFor="crontrol_args">
							{ __( 'Arguments (optional)', 'wp-crontrol' ) }
						</label>
					</th>
					<td>
						<input type="text" autoCorrect="off" autoCapitalize="off" spellCheck="false" className="regular-text code" id="crontrol_args" name="crontrol_args" defaultValue={ args } aria-describedby="crontrol_args_description" />
							<p className="description" id="crontrol_args_description">
								Use a JSON encoded array, e.g. <code>[25]</code>, <code>["asdf"]</code>, or <code>["i","want",25,"cakes"]</code>
							</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label htmlFor="crontrol_next_run_date_local_custom_date">
							{ __( 'Next Run', 'wp-crontrol' ) }
						</label>
					</th>
					<td>
						<input type="hidden" name="crontrol_next_run_date_local" value="custom" />
						<input type="date" autoCorrect="off" autoCapitalize="off" spellCheck="false" name="crontrol_next_run_date_local_custom_date" id="crontrol_next_run_date_local_custom_date" value="2025-03-06" placeholder="yyyy-mm-dd" pattern="\d{4}-\d{2}-\d{2}" />
						<input type="time" autoCorrect="off" autoCapitalize="off" spellCheck="false" name="crontrol_next_run_date_local_custom_time" id="crontrol_next_run_date_local_custom_time" value="15:59:03" step="1" placeholder="hh:mm:ss" pattern="\d{2}:\d{2}:\d{2}" />

						<p className="description">
							{ __( 'Timezone: UTC', 'wp-crontrol' ) }
						</p>
					</td>
				</tr>
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
			</table>
			<p className="submit">
				<input type="submit" className="button button-primary button-large" value={ __( 'Update Event', 'wp-crontrol' ) } />
			</p>
		</form>
	);
}
