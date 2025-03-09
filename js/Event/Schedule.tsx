import React from "react";
import { __ } from "@wordpress/i18n";

type Schedules = {
	name: string;
	display: string;
};

interface ScheduleProps {
	schedule: string;
}

export default function Schedule({
	schedule,
}: ScheduleProps) {
	const [selectedSchedule, setSelectedSchedule] = React.useState(schedule);
	const handleScheduleChange = (event) => {
		setSelectedSchedule(event.target.value);
	};

	const schedules: Schedules[] = window.wpCrontrol.schedules;

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
				<select className="postform" name="crontrol_schedule" id="crontrol_edit_schedule" required value={ selectedSchedule } onChange={ handleScheduleChange }>
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
