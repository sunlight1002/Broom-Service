import React, { useState, useEffect, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import './Board.css';
import Sidebar from '../../Layouts/Sidebar';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { GrUpgrade } from "react-icons/gr";
import { ReactSortable, Sortable, MultiDrag, Swap } from "react-sortablejs";
import { v4 as uuidv4 } from 'uuid';
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Select from "react-select";
import Moment from "moment";

import { useAlert } from "react-alert";
import CommentModal from './CommentModal';
import TaskModal from './TaskModal';
import debounce from "lodash.debounce";
import FilterButtons from '../../../Components/common/FilterButton';

const App = () => {
    const { t } = useTranslation();
    const [team, setTeam] = useState([]);
    const [tasks, setTasks] = useState([])
    const [filteredTasks, setFilteredTasks] = useState([]);
    const [statusOptions, setStatusOptions] = useState([]);
    const [workerOptions, setWorkerOptions] = useState([]);
    const [teamOptions, setTeamOptions] = useState([]);
    const [selectedStatus, setSelectedStatus] = useState("");
    const [selectedWorker, setSelectedWorker] = useState(null);
    const [selectedTeam, setSelectedTeam] = useState(null);
    const [selectedDueDate, setSelectedDueDate] = useState("");
    const [search, setSearch] = useState("");
    const [order, setOrder] = useState("ASC");
    const [sortCol, setSortCol] = useState("due_date");
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
    const [selectedPhaseId, setSelectedPhaseId] = useState(1);
    const [selectedTaskId, setSelectedTaskId] = useState(null);
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [selectedWorkers, setSelectedWorkers] = useState([]);
    const [selectedFrequency, setSelectedFrequency] = useState(1);
    const [repeatancy, setRepeatancy] = useState('');
    const [untilDate, setUntilDate] = useState('');
    const [page, setPage] = useState(1);
    const [pageCount, setPageCount] = useState(1);
    const [loading, setLoading] = useState("Loading...");
    const pageSize = 10;
    const tableRef = useRef();
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState('All');
    const [datePeriod, setDatePeriod] = useState('');
    const [dateRange, setDateRange] = useState({ start: '', end: '' });

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
        try {
            const res = await axios.post(`/api/admin/tasks/${taskId}/move`, { phase_id: phaseId }, { headers });
            getTasks();
            if (res?.data?.message) {
                alert.success(res.data.message);
            }
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to move task. Please try again.');
            }
        }
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
            const response = await axios.get(`/api/admin/workers`, { headers });
            // DataTables structure: response.data.data is the array
            const workers = (response.data.data || []).map(worker => ({
                value: worker.id,
                label: (worker.firstname || '') + ' ' + (worker.lastname || '')
            }));
            setWorkerOptions(workers);
        } catch (error) {
            setWorkerOptions([]);
        }
    }

    const getTeams = async () => {
        try {
            const response = await axios.get(`/api/admin/teams`, { headers });
            // DataTables structure: response.data.data is the array
            const teams = (response.data.data || []).map(team => ({
                value: team.id,
                label: team.name
            }));
            setTeamOptions(teams);
        } catch (error) {
            setTeamOptions([]);
        }
    };

    // Helper to get date range for period
    const getDateRangeForPeriod = (period) => {
        const today = Moment().format('YYYY-MM-DD');
        if (period === 'Day') {
            return { start: today, end: today };
        } else if (period === 'Week') {
            return {
                start: Moment().startOf('week').format('YYYY-MM-DD'),
                end: Moment().endOf('week').format('YYYY-MM-DD'),
            };
        } else if (period === 'Month') {
            return {
                start: Moment().startOf('month').format('YYYY-MM-DD'),
                end: Moment().endOf('month').format('YYYY-MM-DD'),
            };
        }
        return { start: '', end: '' };
    };

    const getTasks = async (params = {}) => {
        setLoading("Loading...");
        try {
            const response = await axios.get(`/api/admin/tasks`, {
                headers,
                params: {
                    status: statusFilter !== 'All' ? statusFilter : '',
                    due_date_start: dateRange.start,
                    due_date_end: dateRange.end,
                    // add other filters as needed
                    ...params
                }
            });
            setTasks(response.data.data);
            setPageCount(response.data.last_page);
            setLoading("");
        } catch (error) {
            setLoading("No tasks found");
            setTasks([]);
        }
    };

    useEffect(() => {
        getTeamMembers();
        getWorkers();
        getTeams();
        getTasks();
        setStatusOptions([
            { value: "Pending", label: "Pending" },
            { value: "In Progress", label: "In Progress" },
            { value: "Completed", label: "Completed" },
        ]);
    }, []);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedSearch(search);
        }, 300);
        return () => {
            clearTimeout(handler);
        };
    }, [search]);

    useEffect(() => {
        getTasks();
        // eslint-disable-next-line
    }, [statusFilter, datePeriod, dateRange.start, dateRange.end, sortCol, order, page]);

    const handleSort = async (sortedTaskIds) => {
        try {
            const res = await axios.post('/api/admin/tasks/sort', { ids: sortedTaskIds }, { headers });
            getTasks();
            if (res?.data?.message) {
                alert.success(res.data.message);
            }
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to reorder tasks. Please try again.');
            }
        }
    };

    const handleAddCard = async () => {

        const userIds = selectedOptions.map(option => option.value);
        const workerIds = selectedWorkers.map(worker => worker.value);
        if (!taskName || !status || !priority || !dueDate || !selectedPhaseId || !userIds.length || !workerIds.length) {
            alert.error('Please fill all required fields.');
            return;
        }


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
            alert.success(response?.data?.message || 'Task created successfully!');
            clearModalFields();
            setIsOpen(false);
            getTasks();
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach((field) => {
                    errors[field].forEach((message) => {
                        alert.error(message);
                    });
                });
            } else if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Something went wrong, please try again.');
            }
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
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to load task details. Please try again.');
            }
        }
    }

    const handleDeleteCard = async (tid) => {
        try {
            const res = await axios.delete(`/api/admin/tasks/${tid}`, { headers });
            alert.success(res?.data?.message || 'Task deleted successfully!');
            getTasks();
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to delete task. Please try again.');
            }
        }
    }

    const handleComment = async () => {
        const data = {
            comment: comment
        }
        try {
            const res = await axios.post(`/api/admin/tasks/${selectedTaskId}/comments`, data, { headers });
            setComments('')
            alert.success(res?.data?.message || 'Comment added successfully!')
            getTasks();
            handleEditTask(selectedTaskId)
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to add comment. Please try again.');
            }
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
        setSelectedOptions(
            task && task.users
                ? task.users.map(user => {
                    // Try to find the label from teamOptions, fallback to user.name
                    const found = teamOptions.find(opt => opt.value === user.id);
                    return found || { value: user.id, label: user.name };
                })
                : []
        );
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
        if (!taskName || !status || !priority || !dueDate) {
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
            alert.success(response?.data?.message || 'Task updated successfully!');
            setIsOpen(false);
            getTasks();
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data.errors) {
                const errors = error.response.data.errors;

                Object.keys(errors).forEach((field) => {
                    errors[field].forEach((message) => {
                        alert.error(message);
                    });
                });
            } else if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Something went wrong, please try again.');
            }
        }
    };

    const handleDeleteComment = async (cid) => {
        try {
            const res = await axios.delete(`/api/admin/comments/${cid}`, { headers })
            alert.success(res?.data?.message || 'Comment deleted successfully!');
            handleEditTask(selectedTaskId);
            getTasks();
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to delete comment. Please try again.');
            }
        }
    }

    const handleEditComment = async (cid, updatedComment) => {
        const data = {
            comment: updatedComment,
        };
        try {
            const res = await axios.put(`/api/admin/tasks/${selectedTaskId}/comments/${cid}`, data, { headers });
            alert.success(res?.data?.message || "Comment updated successfully");
            handleEditTask(selectedTaskId);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to update comment. Please try again.');
            }
        }
    };

    // Remove all frontend-only filtering, searching, and sorting logic
    // Use tasks directly from backend response
    const paginatedTasks = tasks;

    // Sorting
    const sortTable = (col) => {
        if (sortCol === col) {
            setOrder(order === "ASC" ? "DESC" : "ASC");
        } else {
            setSortCol(col);
            setOrder("ASC");
        }
    };

    // Pagination controls
    const handlePageClick = (newPage) => {
        setPage(newPage);
    };

    // Assignment
    const handleAssignWorker = async (taskId, worker) => {
        try {
            const res = await axios.put(`/api/admin/tasks/${taskId}`, { worker_ids: [worker.value] }, { headers });
            getTasks();
            alert.success(res?.data?.message || "Worker assigned successfully");
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error("Failed to assign worker. Please try again.");
            }
        }
    };
    const handleAssignTeam = async (taskId, team) => {
        try {
            const res = await axios.put(`/api/admin/tasks/${taskId}`, { user_ids: [team.value] }, { headers });
            getTasks();
            alert.success(res?.data?.message || "Team member assigned successfully");
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error("Failed to assign team member. Please try again.");
            }
        }
    };

    const taskStatusColor = (status) => {
        const statusLower = status?.toLowerCase() || '';

        console.log({statusLower});
        
        if (statusLower.includes('complete')) {
            return { backgroundColor: '#28a745' };
        } else if (statusLower.includes('progress')) {
            return { backgroundColor: '#ffc107' };
        } else if (statusLower.includes('pending')) {
            return { backgroundColor: '#6c757d' };
        } else {
            return { backgroundColor: '#6c757d' };
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
                        <div className="col text-right">
                            <button className="btn btn-pink addButton" onClick={() => {
                                setSelectedTaskId(null);
                                setIsEditing(false);
                                clearModalFields();
                                setIsOpen(true);
                            }}>
                                <i className="btn-icon fas fa-plus-circle"></i> Add Task
                            </button>
                        </div>
                    </div>
                </div>

                <div className="mb-3">
                    <div className="d-flex align-items-center mb-2">
                        <span style={{ fontWeight: 'bold', marginRight: 10 }}>Filter</span>
                        <FilterButtons text="All" name="All" className="px-3 mr-1" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                        <FilterButtons text="Pending" name="Pending" className="px-3 mr-1" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                        <FilterButtons text="In Progress" name="In Progress" className="px-3 mr-1" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                        <FilterButtons text="Completed" name="Completed" className="px-3 mr-1" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                    </div>
                    <div className="d-flex align-items-center mb-2">
                        <span style={{ fontWeight: 'bold', marginRight: 10 }}>Date Period</span>
                        <FilterButtons text="Day" name="Day" className="px-3 mr-1" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Day')); }} />
                        <FilterButtons text="Week" name="Week" className="px-3 mr-1" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Week')); }} />
                        <FilterButtons text="Month" name="Month" className="px-3 mr-1" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Month')); }} />
                    </div>
                    <div className="d-flex align-items-center">
                        <span style={{ fontWeight: 'bold', marginRight: 10 }}>Date</span>
                        <input type="date" className="form-control mr-2" style={{ width: 170 }} value={dateRange.start} onChange={e => setDateRange({ ...dateRange, start: e.target.value })} />
                        <span className="mx-2">-</span>
                        <input type="date" className="form-control mr-2" style={{ width: 170 }} value={dateRange.end} onChange={e => setDateRange({ ...dateRange, end: e.target.value })} />
                        <button className="btn btn-dark ml-2" onClick={() => { setStatusFilter('All'); setDatePeriod(''); setDateRange({ start: '', end: '' }); }}>Reset</button>
                    </div>
                </div>

                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                <Table className="table table-bordered">
                                    <Thead>
                                        <Tr>
                                            <Th style={{ width: '50px' }}>No.</Th>
                                            <Th style={{ cursor: "pointer" }} onClick={() => sortTable("task_name")}>Task Name <span className="arr">&darr;</span></Th>
                                            <Th style={{ cursor: "pointer" }} onClick={() => sortTable("status")}>Status <span className="arr">&darr;</span></Th>
                                            <Th style={{ cursor: "pointer" }} onClick={() => sortTable("due_date")}>Deadline <span className="arr">&darr;</span></Th>
                                            <Th>Comment</Th>
                                            <Th>Worker/Team Member</Th>
                                            <Th>Actions</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {loading ? (
                                            <Tr>
                                                <Td colSpan={7} className="text-center py-5">
                                                    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: 120 }}>
                                                        <div className="spinner-border text-primary mr-2" role="status">
                                                            <span className="sr-only">Loading...</span>
                                                        </div>
                                                        <span className="ml-2">Loading...</span>
                                                    </div>
                                                </Td>
                                            </Tr>
                                        ) : paginatedTasks.length === 0 ? (
                                            <Tr>
                                                <Td colSpan={7} className="text-center py-5">No tasks found</Td>
                                            </Tr>
                                        ) : (
                                            paginatedTasks.map((task, idx) => (
                                                <Tr key={task.id}>
                                                    <Td>{(page - 1) * pageSize + idx + 1}</Td>
                                                    <Td>{task.task_name}</Td>
                                                    <Td>
                                                        <span 
                                                            style={taskStatusColor(task.status)}
                                                            className="status-badge"
                                                        >
                                                            {task.status}
                                                        </span>
                                                    </Td>
                                                    <Td>{task.due_date ? Moment(task.due_date).format("YYYY-MM-DD") : ""}</Td>
                                                    <Td>
                                                        <button className="btn btn-sm btn-light" onClick={() => handleAddComment(task)}>
                                                            <i className="fa fa-comment"></i> {task.comments?.length || 0}
                                                        </button>
                                                    </Td>
                                                    <Td>
                                                        <div>
                                                            {/* Show current assignees only, no dropdowns */}
                                                            {task.workers && task.workers.length > 0 && (
                                                                <div>
                                                                    <strong>Worker:</strong> {task.workers.map(w => w.name || w.firstname).join(", ")}
                                                                </div>
                                                            )}
                                                            {task.users && task.users.length > 0 && (
                                                                <div>
                                                                    <strong>Team:</strong> {task.users.map(u => u.name).join(", ")}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </Td>
                                                    <Td>
                                                        <button className="btn btn-sm btn-info mr-1" onClick={() => handleOpenEditTaskModal(task)}><i className="fa fa-edit"></i></button>
                                                        <button className="btn btn-sm btn-danger mr-1" onClick={() => handleDeleteCard(task.id)}><i className="fa fa-trash"></i></button>
                                                    </Td>
                                                </Tr>
                                            ))
                                        )}
                                    </Tbody>
                                </Table>
                                {/* Pagination */}
                                <div className="d-flex justify-content-between align-items-center mt-3">
                                    <div>Page {page} of {pageCount}</div>
                                    <div>
                                        <button className="btn btn-light mr-2" disabled={page === 1} onClick={() => handlePageClick(page - 1)}>Prev</button>
                                        <button className="btn btn-light" disabled={page === pageCount} onClick={() => handlePageClick(page + 1)}>Next</button>
                                    </div>
                                </div>
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
                worker={workerOptions}
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
                userType={"admin"}
            />
        </div>
    );
};

export default App;
