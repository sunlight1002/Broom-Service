import React, { useState, useEffect, useRef } from "react";
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { GrUpgrade } from "react-icons/gr";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Select from "react-select";
import Moment from "moment";
import { useAlert } from "react-alert";
import CommentModal from '../../../Admin/Pages/TaskManagement/CommentModal';
import '../../../Admin/Pages/TaskManagement/Board.css';
import Sidebar from "../../Layouts/WorkerSidebar";
import FilterButtons from "../../../Components/common/FilterButton";
import Editor from 'react-simple-wysiwyg';
import TaskDetailModal from '../../../Admin/Pages/TaskManagement/TaskModal';

function Tasks() {
    const { t } = useTranslation();
    const [tasks, setTasks] = useState([]);
    const [statusOptions, setStatusOptions] = useState([]);
    const [search, setSearch] = useState("");
    const [order, setOrder] = useState("ASC");
    const [sortCol, setSortCol] = useState("due_date");
    const [isComModal, setIsComModal] = useState(false);
    const [comment, setComments] = useState('');
    const [taskComments, setTaskComments] = useState([]);
    const [taskName, setTaskName] = useState('');
    const alert = useAlert();
    const [selectedTaskId, setSelectedTaskId] = useState(null);
    const [page, setPage] = useState(1);
    const [pageCount, setPageCount] = useState(1);
    const [loading, setLoading] = useState("Loading...");
    const pageSize = 10;
    const tableRef = useRef();
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState('All');
    const [datePeriod, setDatePeriod] = useState('');
    const [dateRange, setDateRange] = useState({ start: '', end: '' });
    const [isDetailModal, setIsDetailModal] = useState(false);
    const [detailTask, setDetailTask] = useState(null);
    const [detailStatus, setDetailStatus] = useState('');
    const [isEditable, setIsEditable] = useState(false);

    const worker_id = localStorage.getItem("worker-id");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
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
            const response = await axios.get(`/api/tasks/worker/${worker_id}`, {
                headers,
                params: {
                    status: statusFilter !== 'All' ? statusFilter : undefined,
                    search: search || undefined,
                    due_date_start: dateRange.start || undefined,
                    due_date_end: dateRange.end || undefined,
                    sort_by: sortCol,
                    sort_order: order,
                    page: page,
                    per_page: pageSize,
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
    }, [statusFilter, datePeriod, dateRange.start, dateRange.end, search, sortCol, order, page]);

    const handleEditTask = async (tid) => {
        try {
            const response = await axios.get(`/api/tasks/${tid}`, { headers });
            setSelectedTaskId(response.data?.id);
            setTaskComments(response.data?.comments);
            setTaskName(response.data?.task_name);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to load task details. Please try again.');
            }
        }
    };

    const handleComment = async () => {
        const data = {
            comment: comment,
            type: "worker"
        };
        try {
            const res = await axios.post(`/api/tasks/${selectedTaskId}/comments`, data, { headers });
            setComments('');
            alert.success(res?.data?.message || 'Comment added successfully!');
            getTasks();
            handleEditTask(selectedTaskId);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to add comment. Please try again.');
            }
        }
    };

    const handleAddComment = async (task) => {
        handleEditTask(task.id);
        setIsComModal(true);
    };

    const getInitials = (name) => {
        return name.split(' ').map(part => part[0]).join('');
    };

    const handleDeleteComment = async (cid) => {
        try {
            const res = await axios.delete(`/api/worker-comment/${cid}`, { headers });
            alert.success(res?.data?.message || 'Comment deleted successfully!');
            handleEditTask(selectedTaskId);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to delete comment. Please try again.');
            }
        }
    };

    const handleEditComment = async (cid, updatedComment) => {
        const data = {
            comment: updatedComment,
        };
        try {
            const res = await axios.put(`/api/tasks/${selectedTaskId}/comments/${cid}`, data, { headers });
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

    const taskStatusColor = (status) => {
        const statusLower = status?.toLowerCase() || '';
        
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

    // Add a handler for row click
    const handleRowClick = (task) => {
        setDetailTask(task);
        setDetailStatus(task.status);
        setIsDetailModal(true);
    };

    // Handle status update in detail modal
    const handleUpdateTaskStatus = async () => {
        if (!detailTask || !detailStatus) {
            alert.error('Please select a status');
            return;
        }

        try {
            // Use the same API structure as admin but with worker token
            const data = {
                phase_id: detailTask.phase_id,
                task_name: detailTask.task_name,
                status: detailStatus,
                priority: detailTask.priority,
                due_date: detailTask.due_date,
                description: detailTask.description,
                // Keep existing worker_ids and user_ids
                worker_ids: detailTask.workers ? detailTask.workers.map(w => w.id) : [],
                user_ids: detailTask.users ? detailTask.users.map(u => u.id) : []
            };

            const response = await axios.put(`/api/tasks/${detailTask.id}`, data, { headers });
            
            alert.success(response?.data?.message || 'Task status updated successfully!');
            setIsDetailModal(false);
            getTasks(); // Refresh the task list
        } catch (error) {
            console.error(error);
            if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to update task status. Please try again.');
            }
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

                {/* Filters Section */}
                <div className="card mb-4 p-3" style={{ background: '#f8f9fa', border: '1px solid #e9ecef' }}>
                    {/* Top filter row */}
                    <div className="d-flex flex-wrap align-items-center mb-3">
                        <div className="mr-4 mb-2">
                            <div style={{ fontWeight: 'bold', marginBottom: 4 }}>Status</div>
                            <div className="d-flex flex-wrap">
                                <FilterButtons text="All" name="All" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="Pending" name="Pending" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="In Progress" name="In Progress" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="Completed" name="Completed" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                            </div>
                        </div>
                        <div className="mr-4 mb-2">
                            <div style={{ fontWeight: 'bold', marginBottom: 4 }}>Date Period</div>
                            <div className="d-flex flex-wrap">
                                <FilterButtons text="Day" name="Day" className="px-3 mr-2 mb-2" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Day')); }} />
                                <FilterButtons text="Week" name="Week" className="px-3 mr-2 mb-2" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Week')); }} />
                                <FilterButtons text="Month" name="Month" className="px-3 mr-2 mb-2" selectedFilter={datePeriod} setselectedFilter={val => { setDatePeriod(val); setDateRange(getDateRangeForPeriod('Month')); }} />
                            </div>
                        </div>
                        <div className="mb-2">
                            <div style={{ fontWeight: 500, marginBottom: 4 }}>Date Range</div>
                            <div className="d-flex align-items-center flex-wrap">
                                <input type="date" className="form-control mr-2 mb-2" style={{ width: 130 }} value={dateRange.start} onChange={e => setDateRange({ ...dateRange, start: e.target.value })} />
                                <span className="mx-1 mb-2">-</span>
                                <input type="date" className="form-control mr-2 mb-2" style={{ width: 130 }} value={dateRange.end} onChange={e => setDateRange({ ...dateRange, end: e.target.value })} />
                                <button className="btn btn-dark ml-2 mb-2" style={{ minWidth: 70 }} onClick={() => { setStatusFilter('All'); setDatePeriod(''); setDateRange({ start: '', end: '' }); setSearch(''); }}>Reset</button>
                            </div>
                        </div>
                    </div>
                    {/* Second row: Search */}
                    <div className="row align-items-end">
                        <div className="col-md-6 col-12 mb-2">
                            <label style={{ fontWeight: 500 }}>Search</label>
                            <input
                                type="text"
                                className="form-control"
                                placeholder="Search by name or description"
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                {/* Tasks Table */}
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
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {loading ? (
                                            <Tr>
                                                <Td colSpan={6} className="text-center py-5">
                                                    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: 120 }}>
                                                        <div className="spinner-border text-primary mr-2" role="status">
                                                            <span className="sr-only">Loading...</span>
                                                        </div>
                                                        <span className="ml-2">Loading...</span>
                                                    </div>
                                                </Td>
                                            </Tr>
                                        ) : tasks.length === 0 ? (
                                            <Tr>
                                                <Td colSpan={6} className="text-center py-5">No tasks found</Td>
                                            </Tr>
                                        ) : (
                                            tasks.map((task, idx) => (
                                                <Tr key={task.id} onClick={() => handleRowClick(task)} style={{ cursor: 'pointer' }}>
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
                                                    <Td>{task.due_date}</Td>
                                                    <Td>
                                                        <button className="btn btn-sm btn-light" onClick={e => { e.stopPropagation(); handleAddComment(task); }}>
                                                            <i className="fa fa-comment"></i> {task.comments?.length || 0}
                                                        </button>
                                                    </Td>
                                                    <Td>
                                                        <div>
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
                userType="worker"
            />
            {/* Task Detail Modal (view only) */}
            <TaskDetailModal
                isOpen={isDetailModal}
                setIsOpen={setIsDetailModal}
                isEditing={false}
                taskName={detailTask?.task_name || ''}
                setTaskName={() => {}}
                selectedOptions={[]}
                handleSelectChange={() => {}}
                selectedWorkers={[]}
                handleWorkerSelectChange={() => {}}
                priority={detailTask?.priority || ''}
                setPriority={() => {}}
                dueDate={detailTask?.due_date || ''}
                setDueDate={() => {}}
                status={detailStatus}
                setStatus={setDetailStatus}
                handleUpdateTask={handleUpdateTaskStatus}
                handleAddCard={() => {}}
                team={detailTask?.users || []}
                worker={detailTask?.workers || []}
                setDescription={() => {}}
                description={detailTask?.description || ''}
                setSelectedFrequency={() => {}}
                selectedFrequency={detailTask?.frequency_id || 1}
                setRepeatancy={() => {}}
                repeatancy={detailTask?.repeatancy || ''}
                setUntilDate={() => {}}
                untilDate={detailTask?.until_date || ''}
                modalType="detail"
            />
        </div>
    );
}

export default Tasks;
