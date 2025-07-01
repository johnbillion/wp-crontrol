import React, { useState } from "react";
import { __, sprintf } from "@wordpress/i18n";

interface NextRunOptionsProps {
	onChange: (option: string, customDate?: string, customTime?: string) => void;
	timezone: string;
}

export default function NextRunOptions({ onChange, timezone }: NextRunOptionsProps) {
	const [selectedOption, setSelectedOption] = useState('now');
	const [customDate, setCustomDate] = useState('');
	const [customTime, setCustomTime] = useState('');

	// Get today's date in YYYY-MM-DD format for the date input default
	const today = new Date();
	const todayString = today.getFullYear() + '-' +
		String(today.getMonth() + 1).padStart(2, '0') + '-' +
		String(today.getDate()).padStart(2, '0');

	// Get current time in HH:MM:SS format for the time input default
	const currentTime = String(today.getHours()).padStart(2, '0') + ':' +
		String(today.getMinutes()).padStart(2, '0') + ':' +
		String(today.getSeconds()).padStart(2, '0');

	React.useEffect(() => {
		if (!customDate) setCustomDate(todayString);
		if (!customTime) setCustomTime(currentTime);
	}, [todayString, currentTime]);

	const handleOptionChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		const option = event.target.value;
		setSelectedOption(option);
		onChange(option, customDate, customTime);
	};

	const handleCustomDateChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		const date = event.target.value;
		setCustomDate(date);
		setSelectedOption('custom');
		onChange('custom', date, customTime);
	};

	const handleCustomTimeChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		const time = event.target.value;
		setCustomTime(time);
		setSelectedOption('custom');
		onChange('custom', customDate, time);
	};

	const handleCustomDateFocus = () => {
		setSelectedOption('custom');
		onChange('custom', customDate, customTime);
	};

	const handleCustomTimeFocus = () => {
		setSelectedOption('custom');
		onChange('custom', customDate, customTime);
	};

	return (
		<tr>
			<th scope="row">
				{__( 'Next Run', 'wp-crontrol' )}
			</th>
			<td>
				<fieldset>
					<legend className="screen-reader-text">
						{__( 'Next Run', 'wp-crontrol' )}
					</legend>
					<p>
						<label>
							<input
								type="radio"
								name="crontrol_next_run_date_local"
								value="now"
								checked={selectedOption === 'now'}
								onChange={handleOptionChange}
							/>
							{__( 'Now', 'wp-crontrol' )}
						</label>
					</p>
					<p>
						<label>
							<input
								type="radio"
								name="crontrol_next_run_date_local"
								value="+1 day"
								checked={selectedOption === '+1 day'}
								onChange={handleOptionChange}
							/>
							{__( 'Tomorrow', 'wp-crontrol' )}
						</label>
					</p>
					<p>
						<label>
							<input
								type="radio"
								name="crontrol_next_run_date_local"
								value="custom"
								checked={selectedOption === 'custom'}
								onChange={handleOptionChange}
							/>
							{__( 'At this time:', 'wp-crontrol' )}
							<br /><br />
							<input
								type="date"
								name="crontrol_next_run_date_local_custom_date"
								value={customDate}
								onChange={handleCustomDateChange}
								onFocus={handleCustomDateFocus}
								placeholder="yyyy-mm-dd"
								pattern="\d{4}-\d{2}-\d{2}"
								autoCorrect="off"
								autoCapitalize="off"
								spellCheck={false}
							/>
							{' '}
							<input
								type="time"
								name="crontrol_next_run_date_local_custom_time"
								value={customTime}
								onChange={handleCustomTimeChange}
								onFocus={handleCustomTimeFocus}
								step="1"
								placeholder="hh:mm:ss"
								pattern="\d{2}:\d{2}:\d{2}"
								autoCorrect="off"
								autoCapitalize="off"
								spellCheck={false}
							/>
						</label>
					</p>
				</fieldset>
				<p className="description">
					{ sprintf(
						/* translators: %s Timezone name. */
						__( 'Timezone: %s', 'wp-crontrol' ),
						timezone
					) }
				</p>
			</td>
		</tr>
	);
}
