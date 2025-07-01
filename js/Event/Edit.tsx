import React from "react";
import { __ } from "@wordpress/i18n";
import Arguments from "./Arguments";
import HookName from "./HookName";
import NextRun from "./NextRun";
import Schedule from "./Schedule";
import EventName from "./EventName";
import URLFields from "./URLFields";
import PHPFields from "./PHPFields";
import EventType from "./EventType";
import NextRunOptions from "./NextRunOptions";

import type { EventSchedule } from "../index";

interface EditProps {
	args: string;
	codeEditor: unknown;
	date: string;
	integrityCheck: boolean;
	name: string;
	nonce: string;
	protectedHook: boolean;
	schedule: string;
	schedules: EventSchedule[];
	sig: string;
	time: string;
	timestamp: string;
	type: string;
	timezone: string;
	isNew?: boolean;
}

export default function Edit({
	args,
	codeEditor,
	date,
	integrityCheck,
	name,
	nonce,
	protectedHook,
	schedule,
	schedules,
	sig,
	time,
	timestamp,
	type,
	timezone,
	isNew = false,
}: EditProps) {
	let panels;
	let action;
	const argsData = args ? JSON.parse(args) : [];

	const [spinning, setSpinning] = React.useState(false);
	const [eventType, setEventType] = React.useState(isNew ? 'standard' : type);
	const [nextRunOption, setNextRunOption] = React.useState('now');
	const [customDate, setCustomDate] = React.useState('');
	const [customTime, setCustomTime] = React.useState('');

	const handleEventTypeChange = (newType: string) => {
		setEventType(newType);
	};

	const handleNextRunChange = (option: string, customDateValue?: string, customTimeValue?: string) => {
		setNextRunOption(option);
		if (customDateValue !== undefined) setCustomDate(customDateValue);
		if (customTimeValue !== undefined) setCustomTime(customTimeValue);
	};

	const currentType = isNew ? eventType : type;

	switch (currentType) {
		case 'php':
			action = isNew ? 'new_php_cron' : 'edit_php_cron';
			panels = (
				<>
					<PHPFields
						args={ argsData[0] ?? [] }
						codeEditor={ codeEditor }
						integrityCheck={ integrityCheck }
					/>
					<EventName
						name={ argsData[0]?.name ?? '' }
					/>
				</>
			);
			break;
		case 'url':
			action = isNew ? 'new_url_cron' : 'edit_url_cron';
			panels = (
				<>
					<URLFields
						args={ argsData[0] ?? [] }
						integrityCheck={ integrityCheck }
					/>
					<EventName
						name={ argsData[0]?.name ?? '' }
					/>
				</>
			);
			break;
		default:
			action = isNew ? 'new_cron' : 'edit_cron';
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

	const onSubmit = ( event ) => {
		setSpinning(true);
	};

	const spinnerClass = spinning ? 'is-active' : '';

	return (
		<form method="post" action="tools.php?page=wp-crontrol" onSubmit={onSubmit}>
			<input type="hidden" name="_wpnonce" value={ nonce } />
			{!isNew && (
				<>
					<input type="hidden" name="crontrol_original_hookname" value={ name } />
					<input type="hidden" name="crontrol_original_sig" value={ sig } />
					<input type="hidden" name="crontrol_original_next_run_utc" value={ timestamp } />
				</>
			)}
			<input type="hidden" name="crontrol_action" value={ action } />
			{isNew && (
				<>
					<input type="hidden" name="crontrol_next_run_date_local" value={ nextRunOption } />
					{nextRunOption === 'custom' && (
						<>
							<input type="hidden" name="crontrol_next_run_date_local_custom_date" value={ customDate } />
							<input type="hidden" name="crontrol_next_run_date_local_custom_time" value={ customTime } />
						</>
					)}
				</>
			)}
			<table className="form-table">
				<tbody>
					{isNew && (
						<EventType
							type={eventType}
							onChange={handleEventTypeChange}
							canManagePHP={window.wpCrontrol?.canManagePHP ?? false}
						/>
					)}
					{ panels }
					{isNew ? (
						<NextRunOptions
							onChange={handleNextRunChange}
							timezone={timezone}
						/>
					) : (
						<NextRun
							date={ date }
							time={ time }
							timezone={ timezone }
						/>
					)}
					<Schedule
						schedule={ schedule }
						schedules={ schedules }
					/>
				</tbody>
			</table>
			<p className="submit">
				<input type="submit" className="button button-primary button-large" value={ isNew ? __( 'Add Event', 'wp-crontrol' ) : __( 'Update Event', 'wp-crontrol' ) } />
				<span className={`spinner ${spinnerClass}`} style={{
					float: 'none',
					display: 'inline-block',
				}}></span>
			</p>
		</form>
	);
}
