import React, { useState, useEffect, useRef, useCallback } from "react";
import './Board.css';
import Sidebar from '../../Layouts/Sidebar';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import Select from "react-select";
import Moment from "moment";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import { useAlert } from "react-alert";
import CommentModal from './CommentModal';
import TaskModal from './TaskModal';
import FilterButtons from '../../../Components/common/FilterButton';

const App = () => {
    const { t } = useTranslation();
    const [team, setTeam] = useState([]);
    const [workerOptions, setWorkerOptions] = useState([]);
    const [teamOptions, setTeamOptions] = useState([]);
    const [selectedWorker, setSelectedWorker] = useState([]);
    const [selectedTeam, setSelectedTeam] = useState([]);
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
    const [statusFilter, setStatusFilter] = useState('All');
    const [datePeriod, setDatePeriod] = useState('');
    const [dateRange, setDateRange] = useState({ start: '', end: '' });
    const tableRef = useRef(null);
    
    // Use refs to store current filter values for DataTable access
    const filterRefs = useRef({
        statusFilter: 'All',
        dateRange: { start: '', end: '' },
        selectedWorker: [],
        selectedTeam: []
    });

    // Update refs when filters change
    useEffect(() => {
        filterRefs.current = {
            statusFilter,
            dateRange,
            selectedWorker,
            selectedTeam
        };
    }, [statusFilter, dateRange, selectedWorker, selectedTeam]);

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

    const getTasks = useCallback(async (params = {}) => {
        try {
            // Build query parameters
            const queryParams = {
                page: 1, // Assuming DataTables handles pagination
                per_page: 10, // Assuming DataTables handles page size
                sort_by: 'due_date',
                sort_order: 'ASC',
                ...params
            };

            // Add filters only if they have values
            if (statusFilter && statusFilter !== 'All') {
                queryParams.status = statusFilter;
            }
            
            if (dateRange.start) {
                queryParams.due_date_start = dateRange.start;
            }
            
            if (dateRange.end) {
                queryParams.due_date_end = dateRange.end;
            }
            
            if (selectedWorker && selectedWorker.length > 0) {
                queryParams.worker_id = selectedWorker.map(w => w.value);
            }
            
            if (selectedTeam && selectedTeam.length > 0) {
                queryParams.user_id = selectedTeam.map(t => t.value);
            }

            const response = await axios.get(`/api/admin/tasks`, {
                headers,
                params: queryParams,
                paramsSerializer: params => {
                    return Object.keys(params)
                        .map(key => {
                            const value = params[key];
                            if (Array.isArray(value)) {
                                return value.map(v => `${key}[]=${encodeURIComponent(v)}`).join('&');
                            }
                            return `${key}=${encodeURIComponent(value)}`;
                        })
                        .join('&');
                }
            });
            setTasks(response.data.data);
        } catch (error) {
            console.error('Error fetching tasks:', error);
        }
    }, [statusFilter, dateRange, selectedWorker, selectedTeam, headers]);

    useEffect(() => {
        getTeamMembers();
        getWorkers();
        getTeams();
    }, []);

    // DataTable initialization
    useEffect(() => {
        if (tableRef.current) {
            const table = $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/tasks",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        // Add custom filters to DataTables request
                        // Access current filter values from refs
                        const currentFilters = filterRefs.current;
                        
                        if (currentFilters.statusFilter && currentFilters.statusFilter !== 'All') {
                            d.status = currentFilters.statusFilter;
                        }
                        if (currentFilters.dateRange.start) {
                            d.due_date_start = currentFilters.dateRange.start;
                        }
                        if (currentFilters.dateRange.end) {
                            d.due_date_end = currentFilters.dateRange.end;
                        }
                        if (currentFilters.selectedWorker && currentFilters.selectedWorker.length > 0) {
                            d.worker_id = currentFilters.selectedWorker.map(w => w.value);
                        }
                        if (currentFilters.selectedTeam && currentFilters.selectedTeam.length > 0) {
                            d.user_id = currentFilters.selectedTeam.map(t => t.value);
                        }
                        return d;
                    }
                },
                order: [[3, "desc"]], // Sort by due_date by default
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
                            
                            return `<span class="status-badge" style="background-color: ${backgroundColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${data}</span>`;
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
                            return `<button class="btn btn-sm btn-light dt-comment-btn" data-task-id="${row.id}">
                                <i class="fa fa-comment"></i> ${commentCount}
                            </button>`;
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
                    },
                    {
                        title: "Actions",
                        data: null,
                        orderable: false,
                        responsivePriority: 1,
                        render: function (data, type, row, meta) {
                            return `<button class="btn btn-sm btn-info mr-1 dt-edit-btn" data-task-id="${row.id}">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger mr-1 dt-delete-btn" data-task-id="${row.id}">
                                <i class="fa fa-trash"></i>
                            </button>`;
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
            const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
            $("div.dt-search").append(searchInputWrapper);
            $("div.dt-search").addClass("position-relative");

            // Handle comment button clicks
            $(tableRef.current).on("click", ".dt-comment-btn", function (e) {
                e.preventDefault();
                const taskId = $(this).data("task-id");
                handleAddComment({ id: taskId });
            });

            // Handle edit button clicks
            $(tableRef.current).on("click", ".dt-edit-btn", function (e) {
                e.preventDefault();
                const taskId = $(this).data("task-id");
                // Find the task data and call handleOpenEditTaskModal
                const rowData = table.row($(this).closest('tr')).data();
                handleOpenEditTaskModal(rowData);
            });

            // Handle delete button clicks
            $(tableRef.current).on("click", ".dt-delete-btn", function (e) {
                e.preventDefault();
                const taskId = $(this).data("task-id");
                handleDeleteCard(taskId);
            });

            return function cleanup() {
                if (tableRef.current && $(tableRef.current).DataTable()) {
                    $(tableRef.current).DataTable().destroy(true);
                }
            };
        }
    }, []);

    // Refresh table when filters change
    useEffect(() => {
        if (tableRef.current && $(tableRef.current).DataTable()) {
            $(tableRef.current).DataTable().ajax.reload();
        }
    }, [statusFilter, dateRange, selectedWorker, selectedTeam]);

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
            // Reload DataTable to show the new task
            if (tableRef.current && $(tableRef.current).DataTable()) {
                $(tableRef.current).DataTable().ajax.reload();
            }
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
            // Reload DataTable to remove the deleted task
            if (tableRef.current && $(tableRef.current).DataTable()) {
                $(tableRef.current).DataTable().ajax.reload();
            }
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
            handleEditTask(selectedTaskId)
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
    }

    const handleAddComment = async (task) => {
        handleEditTask(task.id);
        setIsComModal(true)
    }

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
            // Reload DataTable to show the updated task
            if (tableRef.current && $(tableRef.current).DataTable()) {
                $(tableRef.current).DataTable().ajax.reload();
            }
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
            if (error.response && error.response.data && error.response.data.error === 'Unauthorized') {
                alert.error('You can only delete your own comment.');
            } else if (error.response && error.response.data && error.response.data.message) {
                alert.error(error.response.data.message);
            } else {
                alert.error('Failed to delete comment. Please try again.');
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

                <div className="card mb-4 p-3" style={{ background: '#f8f9fa', border: '1px solid #e9ecef' }}>
                    {/* Top filter row */}
                    <div className="d-flex flex-wrap align-items-center mb-3">
                        <div className="mr-4 mb-2">
                            <div style={{ fontWeight: 'bold', marginBottom: 4 }}>Status</div>
                            <div className="d-flex flex-wrap">
                                <FilterButtons text="All" name="All" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="Pending" name="Pending" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="In Progress" name="In Progress" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
                                <FilterButtons text="Complete" name="Complete" className="px-3 mr-2 mb-2" selectedFilter={statusFilter} setselectedFilter={setStatusFilter} />
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
                                <button className="btn btn-dark ml-2 mb-2" style={{ minWidth: 70 }} onClick={() => { setStatusFilter('All'); setDatePeriod(''); setDateRange({ start: '', end: '' }); setSelectedWorker([]); setSelectedTeam([]); }}>Reset</button>
                            </div>
                        </div>
                    </div>
                    {/* Second row: Worker, Team Member */}
                    <div className="row align-items-end">
                        <div className="col-md-6 mb-2">
                            <label style={{ fontWeight: 500 }}>Worker</label>
                            <Select
                                options={workerOptions}
                                value={selectedWorker}
                                onChange={setSelectedWorker}
                                isMulti
                                isClearable
                                className="basic-multi-select"
                                classNamePrefix="select"
                                placeholder="Select workers..."
                            />
                        </div>
                        <div className="col-md-6 mb-2">
                            <label style={{ fontWeight: 500 }}>Team Member</label>
                            <Select
                                options={teamOptions}
                                value={selectedTeam}
                                onChange={setSelectedTeam}
                                isMulti
                                isClearable
                                className="basic-multi-select"
                                classNamePrefix="select"
                                placeholder="Select team members..."
                            />
                        </div>
                    </div>
                </div>

                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered custom-datatable"
                            />
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
                setIsComModal={handleCloseCommentModal}
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
