import axios from 'axios';
import React, { useEffect, useState } from 'react';
import { useAlert } from "react-alert";
import { Button, Modal } from "react-bootstrap";
import { useTranslation } from 'react-i18next';
import '../../../Admin/Pages/TaskManagement/Board.css';
import Sidebar from "../../Layouts/WorkerSidebar";

import CommentModal from '../../../Admin/Pages/TaskManagement/CommentModal';
import TaskModal from '../../../Admin/Pages/TaskManagement/TaskModal';

function Tasks() {
    const [selectedTask, setSelectedTask] = useState(null);
    const [showModal, setShowModal] = useState(false);

    const worker_id = localStorage.getItem("worker-id");

    const { t } = useTranslation();
    const [team, setTeam] = useState([]);
    const [worker, setWorker] = useState([])
    const [phase, setPhase] = useState([]);
    const [phaseEdit, setPhaseEdit] = useState(null);
    const [isAddingPhase, setIsAddingPhase] = useState(false);
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

    const handleSelectChange = (selectedOptions) => {
        setSelectedOptions(selectedOptions);
    };

    const handleWorkerSelectChange = (selectedWorkers) => {
        setSelectedWorkers(selectedWorkers);
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };


    const handleAddCard = async () => {
        if (!taskName || !status || !priority || !dueDate || !selectedPhaseId) {
            alert.error('Please fill all required fields.');
            return;
        }

        const userIds = selectedOptions?.map(option => option.value);
        const workerIds = selectedWorkers?.map(worker => worker.value);

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
            const response = await axios.post(`/api/tasks`, data, { headers });
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

    const handleEditTask = async (tid) => {
        try {
            const response = await axios.get(`/api/tasks/${tid}`, { headers })
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


    const updatePhase = async (phaseId, listIndex) => {
        const data = phase[listIndex];
        try {
            const res = await axios.put(`/api/phase/${phaseId}`, data, { headers });
            getPhase()
            setPhaseEdit(null)
        } catch (error) {
            console.error(error);
        }
    }

    const handleAddCommentModal = async (task) => {
        handleEditTask(task.id);
        // console.log(task);
        setIsComModal(true)
    }

    const getInitials = (name) => {
        return name.split(' ')?.map(part => part[0]).join('');
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
    };

    const handleDeleteComment = async (cid) => {
        try {
            const res = await axios.delete(`/api/worker-comment/${cid}`, { headers })
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
            const res = await axios.put(`/api/tasks/${selectedTaskId}/comments/${cid}`, data, { headers });
            alert.success("Comment updated successfully");
            handleEditTask(selectedTaskId);
        } catch (error) {
            alert.error("Something went wrong");
        }
    };

    const getTaskList = async () => {
        try {
            const res = await axios.get(`/api/tasks/worker/${worker_id}`, { headers });
            console.log(res.data);

            setTasks(res.data);
        } catch (error) {
            console.log(error);
        }
    };

    useEffect(() => {
        getTaskList();
    }, []);


    const handleAddComment = async () => {
        if (comment.trim()) {
            const data = {
                comment: comment,
                type: "worker"
            };
            console.log(data);

            try {
                const res = await axios.post(`/api/tasks/${selectedTaskId}/comments`, data, { headers });
                // console.log(res);
                setTasks(tasks?.map(task =>
                    task.id === selectedTaskId
                        ? { ...task, comments: [...task.comments, res.data.comment] }
                        : task
                ));
                setComments('')
                getTaskList()
                setShowModal(false);
                handleEditTask(selectedTaskId)
            } catch (error) {
                console.error(error);
            }
        }
    };

    const changeStatus = async () => {
        try {
            const response = await axios.post('/api/tasks/change-worker-status', {
                id: selectedTaskId,
                status: status
            }, { headers });
            console.log(response.data); // Handle success
            getTaskList()
            setIsOpen(false)
            handleEditTask(selectedTaskId)
        } catch (error) {
            console.error(error.response.data); // Handle error
        }
    };


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Tasks</h1>
                        </div>
                    </div>
                </div>

                <div className="dashBox" style={{ backgroundColor: "inherit", border: "none" }}>
                    <div id='ko' style={{ overflowX: "scroll", maxWidth: "100%" }}>
                        <div id="main" className="d-flex">
                            {tasks?.length > 0 ? tasks?.map((task) => {
                                // const adminComments = task?.comments?.filter(c => c.commentable_type !== "App\\Models\\User");
                                // console.log(adminComments);

                                return (
                                    <div className="list" key={task.id}>
                                        <div className='d-flex align-items-center mb-2'>
                                            <input
                                                type="text"
                                                className="list-title editable mb-0"
                                                value={task?.phase.phase_name}  // Adjust if phase_name isn't directly on task
                                                readOnly={true}
                                            />
                                        </div>

                                        <div className="content">
                                            <div className="taskcard" key={task.id}>
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
                                                            {task?.workers?.map(worker => (
                                                                <div key={worker.id} className="user-icon">
                                                                    {getInitials(worker.firstname)}
                                                                </div>
                                                            ))}
                                                        </div>
                                                        <div className="user-icons">
                                                            {task?.users?.map(user => (
                                                                <div key={user.id} className="user-icon">
                                                                    {getInitials(user.name)}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                    <div className="task-actions d-flex">
                                                        <div className='d-flex '>
                                                            <button className="mr-1 btn-add-comment" style={{ fontSize: "14px", color: 'rgb(65 50 50)' }} onClick={() => handleAddCommentModal(task)}>
                                                                <span className='mr-2'>{task?.comments?.length}</span>
                                                                <i className="fa-solid fa-comment-dots"></i>
                                                            </button>
                                                        </div>
                                                        <button className="mr-1 btn-edit" style={{ fontSize: "14px", color: 'rgb(65 50 50)' }} onClick={() => handleOpenEditTaskModal(task)}>
                                                            <i className="fa-solid fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            }) : (
                                <div>No tasks available</div>
                            )}
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
                handleUpdateTask={changeStatus}
                handleAddCard={handleAddCard}
                team={team}
                worker={worker}
                description={description}
                setDescription={setDescription}
                type={"worker"}
            />


            <CommentModal
                comment={comment}
                isComModal={isComModal}
                setIsComModal={setIsComModal}
                handleComment={handleAddComment}
                handleEditComment={handleEditComment}
                taskComments={taskComments}
                handleDeleteComment={handleDeleteComment}
                taskName={taskName}
                setComments={setComments}
                isEditable={isEditable}
                setIsEditable={setIsEditable}
                userType="worker"
            />
        </div>
    );
}

export default Tasks;
