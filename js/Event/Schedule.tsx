import React from "react";
import { __ } from "@wordpress/i18n";

import type { EventSchedule } from "../index";

interface ScheduleProps {
	schedule: string;
	schedules: EventSchedule[];
}

export default function Schedule({
	schedule,
	schedules,
}: ScheduleProps) {
	const [selectedSchedule, setSelectedSchedule] = React.useState(schedule);
	const handleScheduleChange = (event) => {
		setSelectedSchedule(event.target.value);
	};

	React.useEffect(() => {
		setSelectedSchedule(schedule);
	}, [schedule]);

	return (
		<tr>
			<th scope="row">
				<label htmlFor="crontrol_edit_schedule">
					{ __( 'Schedule', 'wp-crontrol' ) }
				</label>
			</th>
			<td>
				<select name="crontrol_schedule" id="crontrol_edit_schedule" required value={ selectedSchedule } onChange={ handleScheduleChange }>
					<option value="_oneoff">
						Non-repeating
					</option>
					{ Object.keys(schedules).map((key) => (
						<option key={ schedules[key].name } value={ schedules[key].name }>
							{ schedules[key].display } ({ schedules[key].name })
						</option>
					)) }
				</select>
			</td>
		</tr>
	);
}
