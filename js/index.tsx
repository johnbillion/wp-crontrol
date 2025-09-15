import React, { useState, useEffect } from "react";
import { createRoot } from "react-dom/client";
import Edit from "./Event/Edit";
import Modal from "./Modal";
import { __ } from "@wordpress/i18n";

interface EditState {
	args: string;
	date: string;
	integrityCheck: boolean;
	name: string;
	nonce: string;
	protectedHook: boolean;
	schedule: string;
	sig: string;
	time: string;
	timestamp: string;
	type: string;
	isNew: boolean;
}

export type EventSchedule = {
	name: string;
	display: string;
};

export default function App() {
	const [modalState, setModalState] = useState(false);
	const [editState, setEditState] = useState<EditState>({
		args: '',
		date: '',
		integrityCheck: false,
		name: '',
		nonce: '',
		protectedHook: false,
		schedule: '',
		sig: '',
		time: '',
		timestamp: '',
		type: '',
		isNew: false,
	});

	useEffect(() => {
		const editElements = document.querySelectorAll("[data-crontrol-edit]");
		const addElements = document.querySelectorAll("[data-crontrol-add-new]");

		if (!editElements.length && !addElements.length) {
			return; // Ensure there are elements before adding listeners
		}

		const handleEditClick = (event) => {
			// Ignore if any modifiers are pressed
			if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return;
			}

			event.preventDefault();
			const url = new URL(event.target.href);
			const name = url.searchParams.get("crontrol_id");
			const sig = url.searchParams.get("crontrol_sig");
			const timestamp = url.searchParams.get("crontrol_next_run_utc");

			const {
				crontrolArgs: args,
				crontrolDate: date,
				crontrolNonce: nonce,
				crontrolSchedule: schedule,
				crontrolTime: time,
				crontrolType: type,
			} = event.target.dataset;
			const protectedHook = event.target.dataset.crontrolProtected === 'true';
			const integrityCheck = event.target.dataset.crontrolIntegrityCheck === 'true';

			setEditState({
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
				isNew: false,
			});
			setModalState(true);
		};

		const handleAddClick = (event) => {
			// Ignore if any modifiers are pressed
			if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return;
			}

			event.preventDefault();
			const { crontrolNonce: nonce } = event.target.dataset;

			setEditState({
				args: '',
				date: '',
				integrityCheck: false,
				name: '',
				nonce,
				protectedHook: false,
				schedule: '',
				sig: '',
				time: '',
				timestamp: '',
				type: 'standard',
				isNew: true,
			});
			setModalState(true);
		};

		editElements.forEach(element => {
			element.addEventListener("click", handleEditClick);
		});

		addElements.forEach(element => {
			element.addEventListener("click", handleAddClick);
		});

		return () => {
			editElements.forEach(element => {
				element.removeEventListener("click", handleEditClick);
			});
			addElements.forEach(element => {
				element.removeEventListener("click", handleAddClick);
			});
		};
	}, []);

	return (
		<Modal show={modalState} onClose={() => setModalState(false)} title={ editState.isNew ? __( 'Add New Cron Event', 'wp-crontrol' ) : __( 'Edit Cron Event', 'wp-crontrol' ) }>
			<Edit
				args={editState.args}
				codeEditor={window.wpCrontrol.codeEditor}
				date={editState.date}
				integrityCheck={editState.integrityCheck}
				name={editState.name}
				nonce={editState.nonce}
				protectedHook={editState.protectedHook}
				schedule={editState.schedule}
				schedules={window.wpCrontrol.schedules}
				sig={editState.sig}
				time={editState.time}
				timestamp={editState.timestamp}
				type={editState.type}
				timezone={window.wpCrontrol.timezone}
				isNew={editState.isNew}
				key={editState.isNew ? 'new' : editState.sig}
			/>
		</Modal>
	);
}
document.addEventListener('DOMContentLoaded', () => {
	const appElement = document.getElementById('wp-crontrol-app');
	if (appElement) {
		const root = createRoot(appElement);
		root.render(<App />);
	}
});
