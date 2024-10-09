import React, { useState, useEffect } from 'react';
import Sidebar from "../../Layouts/WorkerSidebar";
import { Button, Modal } from "react-bootstrap";
import "./Task.css";
import axios from 'axios';

function Tasks() {
    const [tasks, setTasks] = useState([]);
    const [newComment, setNewComment] = useState('');
    const [selectedTask, setSelectedTask] = useState(null);
    const [showModal, setShowModal] = useState(false);

    const worker_id = localStorage.getItem("worker-id");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
    };

    const getTaskList = async () => {
        try {
            const res = await axios.get(`/api/admin/tasks/worker/${worker_id}`, { headers });
            console.log(res.data);

            setTasks(res.data);
        } catch (error) {
            console.log(error);
        }
    };

    useEffect(() => {
        getTaskList();
    }, []);

    console.log(selectedTask);


    const handleAddComment = async () => {
        if (newComment.trim() && selectedTask) {
            const data = {
                comment: newComment,
            };
            try {
                const res = await axios.post(`/api/admin/tasks/${selectedTask.id}/comments`, data, { headers });
                // console.log(res);

                setTasks(tasks.map(task => 
                    task.id === selectedTask.id 
                        ? { ...task, comments: [...task.comments, res.data.comment] } 
                        : task
                ));
                setNewComment('');
                setShowModal(false);
            } catch (error) {
                console.error(error);
            }
        }
    };

    const handleCommentClick = (task) => {        
        setSelectedTask(task);
        setShowModal(true);
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

                <div className="tasks-container">
                    {tasks.map(task => (
                        <div key={task.id} className="task-card">
                            <h2 className="task-card__title">{task.task_name}</h2>
                            <p className="task-card__description">{task.description}</p>

                            <div className="task-card__comments">
                                <h3>Comments:</h3>
                                {task.comments.length > 0 ? (
                                    <ul>
                                        {task.comments.map((comment, index) => (
                                            <li key={index} className="task-card__comment">
                                                {comment.comment}
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p>No comments yet.</p>
                                )}
                            </div>

                            <div className="task-card__add-comment">
                                <button onClick={() => handleCommentClick(task)} className="navyblue" style={{
                                    padding: '10px 20px',
                                    fontSize: '16px',
                                    cursor: 'pointer',
                                    borderRadius: "5px"
                                }}>Add Comment</button>
                            </div>
                        </div>
                    ))}
                </div>

                <Modal show={showModal} onHide={() => setShowModal(false)} centered>
                    <Modal.Header closeButton>
                        <Modal.Title>Add Comment</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <textarea
                            value={newComment}
                            onChange={(e) => setNewComment(e.target.value)}
                            placeholder="Add a comment..."
                            rows="4"
                            className="modal-textarea"
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowModal(false)}>
                            Close
                        </Button>
                        <Button variant="primary" onClick={handleAddComment}>
                            Add Comment
                        </Button>
                    </Modal.Footer>
                </Modal>
            </div>
        </div>
    );
}

export default Tasks;