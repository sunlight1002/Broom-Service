import React, { useState, useEffect } from 'react';
import { Button, Modal } from "react-bootstrap";
import './Board.css';
import Sidebar from '../../Layouts/Sidebar';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { GrUpgrade } from "react-icons/gr";
import { ReactSortable, Sortable, MultiDrag, Swap } from "react-sortablejs";
import { v4 as uuidv4 } from 'uuid';

import { useAlert } from "react-alert";
import CommentModal from './CommentModal';
import TaskModal from './TaskModal';

const App = () => {
    const { t } = useTranslation();
    const [team, setTeam] = useState([]);
    const [worker, setWorker] = useState([])
    const [phase, setPhase] = useState([]);
    const [phaseEdit, setPhaseEdit] = useState(null);
    const [isAddingPhase, setIsAddingPhase] = useState(false);
    const [newPhaseTitle, setNewPhaseTitle] = useState('');
    const [tasks, setTasks] = useState([])
    const [isOpen, setIsOpen] = useState(false);
    const [isComModal, setIsComModal] = useState(false)
    const [comment, setComments] = useState('');
    const [taskComments, setTaskComments] = useState([])
    const [taskName, setTaskName] = useState('')
    const [priority, setPriority] = useState('');
    const [dueDate, setDueDate] = useState('');
    const [status, setStatus] = useState('')
    const [description, setDescription] = useState("");
    const alert = useAlert();
    const [isEditing, setIsEditing] = useState(false);
    const [isEditable, setIsEditable] = useState(false)
    const [selectedPhaseId, setSelectedPhaseId] = useState(null);
    const [selectedTaskId, setSelectedTaskId] = useState(null);
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [selectedWorkers, setSelectedWorkers] = useState([]);
    const [selectedFrequency, setSelectedFrequency] = useState(1);
    const [repeatancy, setRepeatancy] = useState('');
    const [untilDate, setUntilDate] = useState('');

    const admin_id = localStorage.getItem("admin-id");

    const handleSelectChange = (selectedOptions) => {
        setSelectedOptions(selectedOptions);
    };

    const handleWorkerSelectChange = (selectedWorkers) => {
        setSelectedWorkers(selectedWorkers);
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleMoveTask = async (taskId, phaseId) => {
        const res = await axios.post(`/api/admin/tasks/${taskId}/move`, { phase_id: phaseId }, { headers });
        getTasks();
    };

    const getTeamMembers = async () => {
        try {
            const response = await axios.get(`/api/admin/teams`, { headers });
            const teamData = response?.data?.data?.map(member => ({
                value: member.id,
                label: member.name
            }));
            setTeam(teamData);
        } catch (error) {
            console.error(error);
        }
    };

    const getWorkers = async () => {
        try {
            const response = await axios.get(`/api/admin/workers`, { headers })
            const workers = response?.data?.data?.map(worker => ({
                value: worker.id,
                label: worker.name
            }))
            setWorker(workers)
        } catch (error) {
            console.error(error);
        }
    }

    const getTasks = async () => {
        try {
            const response = await axios.get(`/api/admin/tasks`, { headers })
            setTasks(response.data);

        } catch (error) {
            console.error(error);
        }
    }

    const getPhase = async () => {
        try {
            const response = await axios.get(`/api/admin/phase`, { headers });
            setPhase(response.data);
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        getTeamMembers();
        getWorkers();
        getPhase();
        getTasks();
    }, []);

    const handleSort = async (sortedTaskIds) => {
        try {
            await axios.post('/api/admin/tasks/sort', { ids: sortedTaskIds }, { headers });
            getTasks();
        } catch (error) {
            console.error(error);
            alert.error('Failed to reorder tasks.');
        }
    };

    const handleAddCard = async () => {
        if (!taskName || !status || !priority || !dueDate || !selectedPhaseId) {
            alert.error('Please fill all required fields.');
            return;
        }

        const userIds = selectedOptions.map(option => option.value);
        const workerIds = selectedWorkers.map(worker => worker.value);

        const data = {
            phase_id: selectedPhaseId,
            task_name: taskName,
            due_date: dueDate,
            status: status,
            priority: priority,
            description: description,
            frequency_id: selectedFrequency,
            repeatancy: repeatancy,
            until_date: untilDate,
            ...(userIds.length > 0 && { user_ids: userIds }),
            ...(workerIds.length > 0 && { worker_ids: workerIds })
        };

        try {
            const response = await axios.post(`/api/admin/tasks`, data, { headers });
            alert.success(response?.data?.message);
            clearModalFields();
            setIsOpen(false);
            getTasks();
        } catch (error) {
            if (error.response && error.response.data.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach((field) => {
                    errors[field].forEach((message) => {
                        alert.error(message);
                    });
                });
            } else {
                alert.error('Something went wrong, please try again.');
            }
        }
    };

    const handleAddList = () => {
        setIsAddingPhase(true);
    };

    const handleSavePhase = async () => {
        const data = {
            phase_name: newPhaseTitle
        };
        try {
            await axios.post(`/api/admin/phase`, data, { headers });
            getPhase();
        } catch (error) {
            console.error(error);
        }
        if (newPhaseTitle.trim()) {
            setNewPhaseTitle('');
            setIsAddingPhase(false);
        }
    };

    const handleDeleteList = async (phaseId) => {
        try {
            await axios.delete(`/api/admin/phase/${phaseId}`, { headers });
            getPhase();
        } catch (error) {
            console.error(error);
        }
    };

    const handleEditTask = async (tid) => {
        try {
            const response = await axios.get(`/api/admin/tasks/${tid}`, { headers })
            setSelectedTaskId(response.data?.id)
            setTaskComments(response.data?.comments)
            setTaskName(response.data?.task_name)
        } catch (error) {
            console.error(error);
        }
    }
    // useEffect(() => {
    //     handleEditTask();
    // }, [])


    const handleDeleteCard = async (tid) => {

        try {
            const res = await axios.delete(`/api/admin/tasks/${tid}`, { headers });
            alert.success(res?.data?.message)
            getTasks();
        } catch (error) {
            console.error(error);
        }
    }

    const handleTitleChange = (listIndex, e) => {
        const newPhase = [...phase];
        newPhase[listIndex].phase_name = e.target.value;
        setPhase(newPhase);
    };

    const updatePhase = async (phaseId, listIndex) => {
        const data = phase[listIndex];
        try {
            const res = await axios.put(`/api/admin/phase/${phaseId}`, data, { headers });
            getPhase()
            setPhaseEdit(null)
        } catch (error) {
            console.error(error);
        }
    }


    const handleComment = async () => {
        const data = {
            comment: comment
        }
        try {
            const res = await axios.post(`/api/admin/tasks/${selectedTaskId}/comments`, data, { headers });
            setComments('')
            alert.success(res?.data?.message)
            getTasks();
            handleEditTask(selectedTaskId)
        } catch (error) {
            console.error(error);
        }
    }

    const handleAddComment = async (task) => {
        handleEditTask(task.id);
        setIsComModal(true)
    }

    const getInitials = (name) => {
        return name.split(' ').map(part => part[0]).join('');
    };



    const handleOpenAddTaskModal = (phaseId) => {
        setSelectedPhaseId(phaseId);
        clearModalFields();
        setIsEditing(false);
        setIsOpen(true);
    };

    const handleOpenEditTaskModal = (task) => {
        setSelectedTaskId(task.id);
        setTaskName(task.task_name);
        setPriority(task.priority);
        setDueDate(task.due_date);
        setStatus(task.status);
        setDescription(task.description);
        setSelectedPhaseId(task.phase_id);
        setSelectedOptions(task ? task?.users?.map(user => ({ value: user.id, label: user.name })) : []);
        setSelectedWorkers(task ? task?.workers?.map(worker => ({ value: worker.id, label: worker.firstname })) : []);
        setSelectedFrequency(task.frequency_id);
        setRepeatancy(task.repeatancy);
        setUntilDate(task.until_date);
        setIsEditing(true);
        setIsOpen(true);
    };

    const clearModalFields = () => {
        setTaskName('');
        setPriority('');
        setDueDate('');
        setStatus('');
        setDescription("");
        setSelectedOptions([]);
        setSelectedWorkers([]);
        setSelectedFrequency(1);
        setRepeatancy('');
        setUntilDate('');
    };

    const handleUpdateTask = async () => {
        if (!taskName || !status || !priority || !dueDate || !selectedPhaseId) {
            alert.error('Please fill all required fields.');
            return;
        }

        const userIds = selectedOptions.map(option => option.value);
        const workerIds = selectedWorkers.map(worker => worker.value);

        const data = {
            phase_id: selectedPhaseId,
            task_name: taskName,
            due_date: dueDate,
            status: status,
            priority: priority,
            description: description,
            ...(userIds.length > 0 && { user_ids: userIds }),
            ...(workerIds.length > 0 && { worker_ids: workerIds })
        };

        try {
            const response = await axios.put(`/api/admin/tasks/${selectedTaskId}`, data, { headers });
            alert.success('Task updated successfully!');
            setIsOpen(false);
            getTasks();
        } catch (error) {
            if (error.response && error.response.data.errors) {
                const errors = error.response.data.errors;

                Object.keys(errors).forEach((field) => {
                    errors[field].forEach((message) => {
                        alert.error(message);
                    });
                });
            } else {
                alert.error('Something went wrong, please try again.');
            }
        }
    };

    const handleDeleteComment = async (cid) => {
        try {
            const res = await axios.delete(`/api/admin/comments/${cid}`, { headers })
            alert.success(res?.data?.message)
            handleEditTask(selectedTaskId);
        } catch (error) {
            alert.error("something went wrong")
        }
    }

    const handleEditComment = async (cid, updatedComment) => {
        const data = {
            comment: updatedComment,
        };
        try {
            const res = await axios.put(`/api/admin/tasks/${selectedTaskId}/comments/${cid}`, data, { headers });
            alert.success("Comment updated successfully");
            handleEditTask(selectedTaskId);
        } catch (error) {
            alert.error("Something went wrong");
        }
    };


    return (
        <div id="container">
            <Sidebar />
            <div id="content" style={{ overflowX: "hidden" }}>
                <div className="titleBox customer-title">
                    <div className="row align-items-center justify-space-between">
                        <div className="col">
                            <h1 className="page-title">Task Management</h1>
                        </div>
                    </div>
                </div>

                <div className="dashBox" style={{ backgroundColor: "inherit", border: "none" }}>
                    <div id='ko' style={{ overflowX: "scroll", maxWidth: "100%" }}>
                        <div id="main" className="d-flex" >
                            {phase.length > 0 ? phase.map((list, listIndex) => {
                                const tasksForPhase = tasks.filter(task => task.phase_id === list.id);
                                return (
                                    <div className="list" key={list.id}>
                                        <div className='d-flex align-items-center mb-2'>
                                            <input
                                                type="text"
                                                className="list-title editable mb-0"
                                                value={list.phase_name}
                                                readOnly={phaseEdit !== listIndex}
                                                onChange={(e) => handleTitleChange(listIndex, e)}
                                            />
                                            {
                                                phaseEdit === listIndex ? (
                                                    <button className="p-1 px-2 mr-1 btn-edit" style={{ fontSize: "14px", color: 'rgb(65 50 50)', borderRadius: "5px" }} onClick={() => updatePhase(list.id, listIndex)}>
                                                        <i className="fa-solid fa-arrow-up-from-bracket"></i>
                                                    </button>
                                                ) : (
                                                    <button className="mr-1 p-1 px-2 btn-edit" style={{ fontSize: "14px", color: 'rgb(65 50 50)', borderRadius: "5px" }} onClick={() => setPhaseEdit(listIndex)}>
                                                        <i className="fa-solid fa-edit"></i>
                                                    </button>
                                                )
                                            }
                                            <span className="del" onClick={() => handleDeleteList(list.id)}>&times;</span>
                                        </div>

                                        <div className="content">
                                            <ReactSortable
                                                list={tasksForPhase}
                                                setList={(newTasksForPhase) => {
                                                    const updatedTasks = tasks.map(task => {
                                                        const newTask = newTasksForPhase.find(t => t.id === task.id);
                                                        if (newTask) {
                                                            return { ...task, phase_id: list.id };  // Update the phase_id as needed
                                                        }
                                                        return task;
                                                    });
                                                }}
                                                onEnd={async ({ oldIndex, newIndex, from, to }) => {
                                                    if (oldIndex === newIndex && from === to) return; // No change

                                                    // Get task IDs in the current phase before the move
                                                    const currentTaskIds = tasksForPhase.map(task => task.id);

                                                    // Handle reordering within the same phase
                                                    if (from === to) {
                                                        const reorderedTaskIds = [...currentTaskIds];
                                                        const [movedId] = reorderedTaskIds.splice(oldIndex, 1);  // Remove from old index
                                                        reorderedTaskIds.splice(newIndex, 0, movedId);  // Add at new index

                                                        // Call the handleSort function to update task order in the backend
                                                        await handleSort(reorderedTaskIds);
                                                    } else {
                                                        // Handle moving between phases
                                                        const taskId = tasksForPhase[oldIndex].id;
                                                        // await handleMoveTask(taskIdPhaseId.taskId,taskIdPhaseId.phaseId);
                                                        const destinationPhaseId = $(to).children('div').data('phase-id');  // Extract phase ID from the `to` container
                                                        await handleMoveTask(taskId, destinationPhaseId);
                                                    }
                                                }}
                                                group="tasks"
                                                animation={200}
                                                delayOnDrag={0}
                                                delayOnStart={0}
                                            >
                                                {tasksForPhase.length > 0 ? tasksForPhase.map((task, taskIndex) => (
                                                    <div className="taskcard" data-phase-id={list?.id} key={task.id}>
                                                        <div className="task-info">
                                                            <span className="task-name">{task.task_name}</span>
                                                            <span className="task-priority">
                                                                <i className="fa-solid fa-flag mr-1"></i>{task.priority}
                                                            </span>
                                                        </div>
                                                        <div className="task-details">
                                                            <span><i className="fa-solid fa-calendar-alt"></i> {task.due_date}</span>
                                                            <span><i className="fa-solid fa-tasks"></i> {task.status}</span>
                                                        </div>
                                                        <div className="task-users d-flex justify-content-between">
                                                            <div className='d-flex'>
                                                                <div className="user-icons">
                                                                    {task.workers.map(worker => (
                                                                        <div key={worker.id} className="user-icon">
                                                                            {getInitials(worker.firstname)}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                                <div className="user-icons">
                                                                    {task.users.map(user => (
                                                                        <div key={user.id} className="user-icon">
                                                                            {getInitials(user.name)}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            </div>
                                                            <div className="task-actions">
                                                                <button className="mr-1 btn-add-comment" style={{ fontSize: "14px", color: 'rgb(65 50 50)' }} onClick={() => handleAddComment(task)}>
                                                                    <span className='mr-2'>{task?.comments?.length}</span>
                                                                    <i className="fa-solid fa-comment-dots"></i>
                                                                </button>
                                                                <button className="mr-1 btn-edit" style={{ fontSize: "14px", color: 'rgb(65 50 50)' }} onClick={() => handleOpenEditTaskModal(task)}>
                                                                    <i className="fa-solid fa-edit"></i>
                                                                </button>
                                                                <button className="btn-delete" style={{ fontSize: "14px", color: 'rgb(65 50 50)' }} onClick={() => handleDeleteCard(task.id)}>
                                                                    <i className="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                )) : <div data-phase-id={list?.id}>No tasks available</div>
                                                }
                                            </ReactSortable>
                                        </div>

                                        <div className="add-card editable" onClick={() => handleOpenAddTaskModal(list.id)}>
                                            Add another task
                                        </div>
                                    </div>
                                );
                            }) : ""}

                            <div className="add-phase-container">
                                {!isAddingPhase && (
                                    <button
                                        type='button'
                                        className=' px-3 py-2'
                                        style={{ borderRadius: "5px", width: "8rem" }}
                                        onClick={handleAddList}
                                    >
                                        <i className="fa-solid fa-plus"></i> Add Phase
                                    </button>
                                )}
                                {isAddingPhase && (
                                    <div className="mb-4">
                                        <div className='d-flex'>
                                            <input
                                                type="text"
                                                className="form-control"
                                                placeholder="Enter phase title"
                                                value={newPhaseTitle}
                                                onChange={(e) => setNewPhaseTitle(e.target.value)}
                                            />
                                            <span className="del" onClick={() => setIsAddingPhase(false)}>
                                                &times;
                                            </span>
                                        </div>
                                        <button
                                            className='btn  mt-2'
                                            onClick={handleSavePhase}
                                        >
                                            <i className="fa-solid fa-plus"></i> Add
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <TaskModal
                isOpen={isOpen}
                setIsOpen={setIsOpen}
                isEditing={isEditing}
                taskName={taskName}
                setTaskName={setTaskName}
                selectedOptions={selectedOptions}
                handleSelectChange={handleSelectChange}
                selectedWorkers={selectedWorkers}
                handleWorkerSelectChange={handleWorkerSelectChange}
                priority={priority}
                setPriority={setPriority}
                dueDate={dueDate}
                setDueDate={setDueDate}
                status={status}
                setStatus={setStatus}
                handleUpdateTask={handleUpdateTask}
                handleAddCard={handleAddCard}
                team={team}
                worker={worker}
                description={description}
                setDescription={setDescription}
                setSelectedFrequency={setSelectedFrequency}
                selectedFrequency={selectedFrequency}
                setRepeatancy={setRepeatancy}
                repeatancy={repeatancy}
                setUntilDate={setUntilDate}
                untilDate={untilDate}
            />


            <CommentModal
                comment={comment}
                isComModal={isComModal}
                setIsComModal={setIsComModal}
                handleComment={handleComment}
                handleEditComment={handleEditComment}
                taskComments={taskComments}
                handleDeleteComment={handleDeleteComment}
                taskName={taskName}
                setComments={setComments}
                isEditable={isEditable}
                setIsEditable={setIsEditable}
                userType={"admin"} // Add userType prop (admin/worker)
            />
        </div>
    );
};

export default App;
