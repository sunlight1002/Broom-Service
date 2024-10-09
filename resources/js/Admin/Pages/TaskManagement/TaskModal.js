import React from 'react'
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
    type = 'admin'
}) {
    const { t } = useTranslation();
    function onChange(e) {
        setDescription(e.target.value);
    }

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
                        {isEditing ? "Edit Task" : "Add Task"}
                    </Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="">
                        <div className='d-flex flex-column mb-3'>
                            <p className='navblueColor mb-2 font-18' style={{ fontWeight: "500" }}>Task Name</p>
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
                            <p className='navblueColor mb-2 font-18' style={{ fontWeight: "500" }}>Description</p>
                            <Editor value={description} onChange={onChange} disabled={type === 'worker'} />
                        </div>
                        {
                            type === "admin" && (
                                <div className="row form-group mt-3">
                                    <div className="col-md-6">
                                        <label className="control-label">Team Members</label>
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
                                        <label className="control-label">Workers</label>
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
                        <div className="row form-group mt-3">
                            <div className="col-sm">
                                <div className="d-flex flex-column">
                                    <label className="control-label">Priority</label>
                                    <select
                                        className="form-control"
                                        name="priority"
                                        value={priority || ""}
                                        disabled={type === 'worker'}
                                        onChange={(e) => setPriority(e.target.value)}
                                    >
                                        <option value="">-- Select priority</option>
                                        <option value="high">High</option>
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                            </div>
                            <div className="col-sm">
                                <div className="d-flex flex-column">
                                    <label className="control-label">Due Date</label>
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
                                    <label className="control-label">Status</label>
                                    <select
                                        className="form-control"
                                        name="status"
                                        value={status || ""}
                                        onChange={(e) => setStatus(e.target.value)}
                                    >
                                        <option value="">-- Select status</option>
                                        <option value="pending">Pending</option>
                                        <option value="in progress">In Progress</option>
                                        <option value="complete">Complete</option>
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
                        {isEditing ? "Update Task" : "Add Task"}
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    )
}

export default TaskModal