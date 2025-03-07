import React from "react";
import { __ } from "@wordpress/i18n";
import Arguments from "./Arguments";
import HookName from "./HookName";
import NextRun from "./NextRun";
import Schedule from "./Schedule";
import EventName from "./EventName";
import URLFields from "./URLFields";

export default function Edit({
	args,
	date,
	integrityCheck,
	name,
	nonce,
	protectedHook,
	schedule,
	sig,
	time,
	timestamp,
	type,
}) {
	let panels;
	let action;
	const argsData = args ? JSON.parse(args) : [];

	switch (type) {
		case 'php':
			action = 'edit_php_cron';
			panels = (
				<>
					{/* <PHPCode/> */}
					<EventName
						name={ argsData[0]?.name ?? '' }
					/>
				</>
			);
			break;
		case 'url':
			action = 'edit_url_cron';
			panels = (
				<>
					<URLFields
						args={ argsData[0] ?? [] }
						integrityCheck={ integrityCheck }
					/>
					<EventName
						name={ name }
					/>
				</>
			);
			break;
		default:
			action = 'edit_cron';
			panels = (
				<>
					<HookName
						name={ name }
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
			<input type="hidden" name="crontrol_original_hookname" value={ name } />
			<input type="hidden" name="crontrol_original_sig" value={ sig } />
			<input type="hidden" name="crontrol_original_next_run_utc" value={ timestamp } />
			<input type="hidden" name="crontrol_action" value={ action } />
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
