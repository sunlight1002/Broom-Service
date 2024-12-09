import React, { useState, useEffect } from 'react'
import { Button, Modal } from "react-bootstrap";
import Select from "react-select";
import { useTranslation } from 'react-i18next';
import Editor from 'react-simple-wysiwyg';
// import Select from "react-select";

function TaskModal({
    isOpen,
    setIsOpen,
    isEditing,
    taskName,
    setTaskName,
    selectedOptions,
    handleSelectChange,
    selectedWorkers,
    handleWorkerSelectChange,
    priority,
    setPriority,
    dueDate,
    setDueDate,
    status,
    setStatus,
    handleUpdateTask,
    handleAddCard,
    team,
    worker,
    setDescription,
    description,
    type = 'admin',
    setSelectedFrequency,
    selectedFrequency,
    setRepeatancy,
    repeatancy,
    setUntilDate,
    untilDate,
}) {
    const { t } = useTranslation();
    const [frequencies, setFrequencies] = useState([]);
    function onChange(e) {
        setDescription(e.target.value);
    }

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const getFrequency = (lng = "en") => {
        axios
            .post("/api/admin/all-service-schedule", { lng }, { headers })
            .then((res) => {
                setFrequencies(res.data.schedules);
            });
    };

    useEffect(() => {
        getFrequency();
    }, [])


    return (
        <div>
            <Modal
                size="lg"
                className="modal-container"
                show={isOpen}
                onHide={() => {
                    setIsOpen(false);
                }}
            >
                <Modal.Header closeButton>
                    <Modal.Title>
                        {isEditing ? t("admin.global.edit_task") : t("admin.global.add_task")}
                    </Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="">
                        <div className='d-flex flex-column mb-3'>
                            <p className='navblueColor mb-2 font-18' style={{ fontWeight: "500" }}>{t("admin.global.task_name")}</p>
                            <div className="d-flex flex-column">
                                <input
                                    type="text"
                                    className="form-control"
                                    value={taskName}
                                    disabled={type === 'worker'}
                                    onChange={(e) => setTaskName(e.target.value)}
                                    required
                                    placeholder={'Enter task name'}
                                />
                            </div>
                        </div>
                        <div className='d-flex flex-column'>
                            <p className='navblueColor mb-2 font-18' style={{ fontWeight: "500" }}>{t("admin.global.description")}</p>
                            <Editor value={description} onChange={onChange} disabled={type === 'worker'} />
                        </div>
                        {
                            type === "admin" && (
                                <div className="row form-group mt-3">
                                    <div className="col-md-6">
                                        <label className="control-label">{t("admin.global.team_members")}</label>
                                        <Select
                                            value={selectedOptions}
                                            name="teamMembers"
                                            isMulti
                                            options={team}
                                            className="basic-multi-select"
                                            isClearable={true}
                                            placeholder="-- Please select --"
                                            classNamePrefix="select"
                                            onChange={(e) => handleSelectChange(e)}
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="control-label">{t("admin.global.Workers")}</label>
                                        <Select
                                            value={selectedWorkers}
                                            name="workers"
                                            isMulti
                                            options={worker}
                                            className="basic-multi-select"
                                            isClearable={true}
                                            placeholder="-- Please select --"
                                            classNamePrefix="select"
                                            onChange={(e) => handleWorkerSelectChange(e)}
                                        />
                                    </div>
                                </div>
                            )
                        }
                        {
                            type === "admin" && (
                                <div className="row form-group mt-3">
                                    {/* Frequency Input */}
                                    <div className="col-sm">
                                        <div className="form-group m-0">
                                            <label className="control-label">{t("client.offer.view.frequency")}</label>
                                            <select
                                                name="frequency"
                                                className="form-control mb-2"
                                                value={selectedFrequency}
                                                onChange={(e) => setSelectedFrequency(e.target.value)}
                                            >
                                                {/* <option value={0}>{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option> */}
                                                {frequencies.map((s, i) => (
                                                    <option cycle={s.cycle} period={s.period} name={s.name} value={s.id} key={i}>
                                                        {s.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                    {
                                        selectedFrequency != 1 && (
                                            <>
                                                <div className="col-sm">
                                                    <label className="control-label">
                                                        {t(
                                                            "admin.schedule.jobs.CancelModal.Repeatancy"
                                                        )}
                                                    </label>

                                                    <select
                                                        name="repeatancy"
                                                        onChange={(e) => setRepeatancy(e.target.value)}
                                                        value={repeatancy}
                                                        className="form-control mb-3"
                                                    >
                                                        <option value="">
                                                            {t("admin.global.please_select")}
                                                        </option>
                                                        <option value="forever">
                                                            {t(
                                                                "admin.schedule.jobs.CancelModal.options.Forever"
                                                            )}
                                                        </option>
                                                        <option value="until_date">
                                                            {t(
                                                                "admin.schedule.jobs.CancelModal.options.UntilDate"
                                                            )}
                                                        </option>
                                                    </select>
                                                </div>

                                                {repeatancy == "until_date" && (
                                                    <div className="col-sm">
                                                        <label className="control-label">
                                                            {t(
                                                                "admin.schedule.jobs.CancelModal.options.UntilDate"
                                                            )}
                                                        </label>
                                                        <input
                                                            type="date"
                                                            className="form-control"
                                                            value={untilDate}
                                                            onChange={(e) => setUntilDate(e.target.value)}
                                                            required
                                                        />
                                                    </div>
                                                )}
                                            </>
                                        )
                                    }
                                </div>
                            )
                        }
                        <div className="row form-group mt-3">
                            <div className="col-sm">
                                <div className="d-flex flex-column">
                                    <label className="control-label">{t("admin.global.priority")}</label>
                                    <select
                                        className="form-control"
                                        name="priority"
                                        value={priority || ""}
                                        disabled={type === 'worker'}
                                        onChange={(e) => setPriority(e.target.value)}
                                    >
                                        <option value="">{t("admin.global.select_priority")}</option>
                                        <option value="high">{t("admin.global.high")}</option>
                                        <option value="medium">{t("admin.global.medium")}</option>
                                        <option value="low">{t("admin.global.low")}</option>
                                    </select>
                                </div>
                            </div>
                            <div className="col-sm">
                                <div className="d-flex flex-column">
                                    <label className="control-label">{t("admin.global.due_date")}</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={dueDate}
                                        disabled={type === 'worker'}
                                        onChange={(e) => setDueDate(e.target.value)}
                                        required
                                    />
                                </div>
                            </div>
                            <div className="col-sm">
                                <div className="d-flex flex-column">
                                    <label className="control-label">{t("admin.global.status")}</label>
                                    <select
                                        className="form-control"
                                        name="status"
                                        value={status || ""}
                                        onChange={(e) => setStatus(e.target.value)}
                                    >
                                        <option value="">{t("admin.global.select_status")}</option>
                                        <option value="pending">{t("admin.global.pending")}</option>
                                        <option value="in progress">{t("admin.global.in_progress")}</option>
                                        <option value="complete">{t("admin.global.completed")}</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setIsOpen(false)}
                    >
                        {t("modal.close")}
                    </Button>
                    <Button
                        type="button"
                        className="btn btn-primary"
                        onClick={isEditing ? handleUpdateTask : handleAddCard}
                    >
                        {isEditing ? t("admin.global.update_task") : t("admin.global.add_task")}
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    )
}

export default TaskModal