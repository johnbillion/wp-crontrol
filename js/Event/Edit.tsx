import React from "react";
import { __ } from "@wordpress/i18n";
import Arguments from "./Arguments";
import HookName from "./HookName";
import NextRun from "./NextRun";
import Schedule from "./Schedule";

export default function Edit({
	args,
	date,
	id,
	nonce,
	protectedHook,
	schedule,
	sig,
	time,
	timestamp,
	type,
}) {
	let panels;

	switch (type) {
		case 'php':
			panels = (
				<>
					{/* <PHPCode/> */}
					{/* <EventName/> */}
				</>
			);
			break;
		case 'url':
			panels = (
				<>
					{/* <URLFields/> */}
					{/* <EventName/> */}
				</>
			);
			break;
		default:
			panels = (
				<>
					<HookName
						id={ id }
						protectedHook={ protectedHook }
					/>
					<Arguments
						args={ args }
					/>
				</>
			);
			break;
	}

	return (
		<form method="post" action="tools.php?page=wp-crontrol">
			<input type="hidden" name="_wpnonce" value={ nonce } />
			<input type="hidden" name="crontrol_original_hookname" value={ id } />
			<input type="hidden" name="crontrol_original_sig" value={ sig } />
			<input type="hidden" name="crontrol_original_next_run_utc" value={ timestamp } />
			<input type="hidden" name="crontrol_action" value="edit_cron" />
			<table className="form-table">
				<tbody>
					{ panels }
					<NextRun
						date={ date }
						time={ time }
					/>
					<Schedule
						schedule={ schedule }
					/>
				</tbody>
			</table>
			<p className="submit">
				<input type="submit" className="button button-primary button-large" value={ __( 'Update Event', 'wp-crontrol' ) } />
			</p>
		</form>
	);
}
