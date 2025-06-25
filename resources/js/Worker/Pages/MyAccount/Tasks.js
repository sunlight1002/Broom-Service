import React, { useState, useEffect, useRef, useCallback } from "react";
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { GrUpgrade } from "react-icons/gr";
import Select from "react-select";
import Moment from "moment";
import { useAlert } from "react-alert";
import CommentModal from '../../../Admin/Pages/TaskManagement/CommentModal';
import '../../../Admin/Pages/TaskManagement/Board.css';
import Sidebar from "../../Layouts/WorkerSidebar";
import FilterButtons from "../../../Components/common/FilterButton";
import Editor from 'react-simple-wysiwyg';
import TaskDetailModal from '../../../Admin/Pages/TaskManagement/TaskModal';

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

function Tasks() {
    const { t } = useTranslation();
    const [statusOptions, setStatusOptions] = useState([]);
    const [isComModal, setIsComModal] = useState(false);
    const [comment, setComments] = useState('');
    const [taskComments, setTaskComments] = useState([]);
    const [taskName, setTaskName] = useState('');
    const alert = useAlert();
    const [selectedTaskId, setSelectedTaskId] = useState(null);
    const [datePeriod, setDatePeriod] = useState('');
    const [dateRange, setDateRange] = useState({ start: '', end: '' });
    const [isDetailModal, setIsDetailModal] = useState(false);
    const [detailTask, setDetailTask] = useState(null);
    const [detailStatus, setDetailStatus] = useState('');
    const [isEditable, setIsEditable] = useState(false);
    const [statusFilter, setStatusFilter] = useState('All');
    const [search, setSearch] = useState("");
    const tableRef = useRef(null);

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

    const getTasks = useCallback(async (params = {}) => {
        try {
            // Build query parameters
            const queryParams = {
                page: 1, // Assuming default page 1
                per_page: 10, // Assuming default page size 10
                sort_by: "due_date",
                sort_order: "ASC",
                ...params
            };

            // Add filters only if they have values
            if (statusFilter && statusFilter !== 'All') {
                queryParams.status = statusFilter;
            }
            
            if (search && search.trim()) {
                queryParams.search = search.trim();
            }
            
            if (dateRange.start) {
                queryParams.due_date_start = dateRange.start;
            }
            
            if (dateRange.end) {
                queryParams.due_date_end = dateRange.end;
            }

            const response = await axios.get(`/api/tasks/worker/${worker_id}`, {
                headers,
                params: queryParams
            });
            // Assuming response.data.data contains the tasks
            // You might want to update the state with this data
            console.log(response.data.data);
        } catch (error) {
            console.error('Error fetching tasks:', error);
        }
    }, [statusFilter, search, dateRange, worker_id, headers]);

    useEffect(() => {
        getTasks();
        setStatusOptions([
            { value: "Pending", label: "Pending" },
            { value: "In Progress", label: "In Progress" },
            { value: "Completed", label: "Completed" },
        ]);
    }, []);

    // DataTable initialization
    useEffect(() => {
        if (tableRef.current) {
            const table = $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `/api/tasks/worker/${worker_id}`,
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("worker-token")
                        );
                    },
                    data: function (d) {
                        if (statusFilter && statusFilter !== 'All') {
                            d.status = statusFilter;
                        }
                        if (dateRange.start) {
                            d.due_date_start = dateRange.start;
                        }
                        if (dateRange.end) {
                            d.due_date_end = dateRange.end;
                        }
                        if (search && search.trim()) {
                            d.search = search.trim();
                        }
                        return d;
                    }
                },
                order: [[3, "desc"]],
                columns: [
                    {
                        title: "No.",
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        title: "Task Name",
                        data: "task_name",
                        name: "task_name"
                    },
                    {
                        title: "Status",
                        data: "status",
                        name: "status",
                        render: function (data, type, row, meta) {
                            const statusLower = data?.toLowerCase() || '';
                            let backgroundColor = '#6c757d';
                            if (statusLower.includes('complete')) {
                                backgroundColor = '#28a745';
                            } else if (statusLower.includes('progress')) {
                                backgroundColor = '#ffc107';
                            }
                            return `<span class=\"status-badge\" style=\"background-color: ${backgroundColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;\">${data}</span>`;
                        }
                    },
                    {
                        title: "Deadline",
                        data: "due_date",
                        name: "due_date",
                        render: function (data, type, row, meta) {
                            return data ? Moment(data).format("YYYY-MM-DD") : "";
                        }
                    },
                    {
                        title: "Comment",
                        data: "comments",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            const commentCount = data ? data.length : 0;
                            return `<button class=\"btn btn-sm btn-light dt-comment-btn\" data-task-id=\"${row.id}\">\n                                <i class=\"fa fa-comment\"></i> ${commentCount}\n                            </button>`;
                        }
                    },
                    {
                        title: "Worker/Team Member",
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            let html = '<div>';
                            if (row.workers && row.workers.length > 0) {
                                html += `<div><strong>Worker:</strong> ${row.workers.map(w => w.name || w.firstname).join(", ")}</div>`;
                            }
                            if (row.users && row.users.length > 0) {
                                html += `<div><strong>Team:</strong> ${row.users.map(u => u.name).join(", ")}</div>`;
                            }
                            html += '</div>';
                            return html;
                        }
                    }
                ],
                ordering: true,
                searching: true,
                responsive: true,
                autoWidth: true,
                width: "100%",
                scrollX: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass('custom-row-class');
                },
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class');
                        }
                    }
                ]
            });

            // Customize the search input
            const searchInputWrapper = `<i class=\"fa fa-search search-icon\"></i>`;
            $("div.dt-search").append(searchInputWrapper);
            $("div.dt-search").addClass("position-relative");

            // Handle comment button clicks
            $(tableRef.current).on("click", ".dt-comment-btn", function (e) {
                e.preventDefault();
                const taskId = $(this).data("task-id");
                handleEditTask(taskId);
            });

            return function cleanup() {
                $(tableRef.current).DataTable().destroy(true);
            };
        }
    }, [statusFilter, dateRange, search]);

    // Refresh table when filters change
    useEffect(() => {
        if (tableRef.current && $(tableRef.current).DataTable()) {
            $(tableRef.current).DataTable().ajax.reload();
        }
    }, [statusFilter, dateRange, search]);

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
            handleEditTask(selectedTaskId);
            // Reload DataTable to update comment count
            if (tableRef.current && $(tableRef.current).DataTable()) {
                $(tableRef.current).DataTable().ajax.reload();
            }
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
            if (error.response && error.response.data && error.response.data.error === 'Unauthorized') {
                alert.error('You can only delete your own comment.');
            } else if (error.response && error.response.data && error.response.data.message) {
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
        // Implement sorting logic here
    };

    // Pagination controls
    const handlePageClick = (newPage) => {
        // Implement pagination logic here
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

    // When closing the comment modal, reload the DataTable to update comment count
    const handleCloseCommentModal = () => {
        setIsComModal(false);
        if (tableRef.current && $(tableRef.current).DataTable()) {
            $(tableRef.current).DataTable().ajax.reload();
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
                                            <Th style={{ cursor: "pointer" }} onClick={() => sortTable("task_name")}>
                                                Task Name 
                                                <span className="arr">
                                                    {/* Add sorting arrow logic here */}
                                                </span>
                                            </Th>
                                            <Th style={{ cursor: "pointer" }} onClick={() => sortTable("status")}>
                                                Status 
                                                <span className="arr">
                                                    {/* Add sorting arrow logic here */}
                                                </span>
                                            </Th>
                                            <Th style={{ cursor: "pointer" }} onClick={() => sortTable("due_date")}>
                                                Deadline 
                                                <span className="arr">
                                                    {/* Add sorting arrow logic here */}
                                                </span>
                                            </Th>
                                            <Th>Comment</Th>
                                            <Th>Worker/Team Member</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {/* Add loading state and no tasks found message here */}
                                    </Tbody>
                                </Table>
                                {/* Pagination */}
                                <div className="d-flex justify-content-between align-items-center mt-3">
                                    <div>Page {1} of {/* Add page count here */}</div>
                                    <div>
                                        <button className="btn btn-light mr-2" disabled={true} onClick={() => handlePageClick(1)}>Prev</button>
                                        <button className="btn btn-light" disabled={true} onClick={() => handlePageClick(/* Add page count here */)}>Next</button>
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
                setIsComModal={handleCloseCommentModal}
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
