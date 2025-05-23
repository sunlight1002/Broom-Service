import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import FilterButtons from "../../../Components/common/FilterButton";

const WorkersHearing = ({ worker, getWorkerDetails }) => {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const [isLoading, setIsLoading] = useState(false);
    const tableRef = useRef(null);
    const [filter, setFilter] = useState("All");
    const [isOpen, setIsOpen] = useState(false);
    const [claimText, setClaimText] = useState('');
    const [hearingId, setHearingId] = useState('');
    const [message, setMessage] = useState('');
    const alert = useAlert();

    const adminId = localStorage.getItem("admin-id");
    const id = worker?.id;

    const handleScheduleHearing = () => {
        navigate(`/admin/workers/view/${id}/hearing-invitation`, {
            state: { worker },
        });
    };
    

    // const handleCreateClaim = () => {
    //     navigate(`/admin/workers/view/${id}/create-claim`, {
    //         state: { worker, getWorkerDetails },
    //     });
    // };

    const toggleClaimModal = (id) => {
        setIsOpen(!isOpen);
        setHearingId(id)
    };

    const handleUploadClaim = () => {
        navigate(`/admin/workers/view/${id}/upload-claim`, {
            state: { worker, getWorkerDetails },
        });
    };

    const meetingStatuses = [
        t("admin.schedule.options.meetingStatus.Pending"),
        t("admin.schedule.options.meetingStatus.Confirmed"),
        t("admin.schedule.options.meetingStatus.Completed"),
        t("admin.schedule.options.meetingStatus.Declined"),
        t("admin.schedule.options.meetingStatus.rescheduled"),
    ];
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const handleClaimSubmit = async (event) => {
        event.preventDefault();

        try {
            const response = await axios.post('/api/admin/claims', {
                worker_id: id,
                admin_id: adminId,
                claim: claimText,
                hearing_invitation_id: hearingId,
            }, { headers });


            if (response.status === 200) {
                setIsOpen(false);
                setClaimText('');
                alert.success('Claim created successfully');
            }
        } catch (error) {
            alert.error('Error creating claim');
        }
    };

    const initializeDataTable = () => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/hearing-invitations",
                    type: "GET",
                    data: { worker_id: id },
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("admin.dashboard.pending.scheduled"),
                        // title:"Scheduled",
                        data: "start_date",
                        render: function (data, type, row, meta) {
                            let _html = "";

                            if (row.start_date) {
                                _html += `<span class="text-blue"> ${Moment(
                                    row.start_date
                                ).format("DD/MM/Y")} </span>`;

                                _html += `<br /> <span class="text-blue"> ${Moment(
                                    row.start_date
                                ).format("dddd")} </span>`;

                                if (row.start_time && row.end_time) {
                                    _html += `<br /> <span class="text-green"> Start : ${row.start_time} </span>`;
                                    _html += `<br /> <span class="text-danger"> End : ${row.end_time} </span>`;
                                }
                            }

                            return _html;
                        },
                    },
                    {
                        title: t("admin.global.Name"),
                        title: "Name",
                        data: "firstname",
                        render: function (data, type, row, meta) {
                            return `<a href="/admin/workers/view/${row.worker_id}" target="_blank" class="dt-client-link"> ${data} </a>`;
                        },
                    },
                    {
                        title: t("admin.dashboard.pending.contact"),
                        title: "Contact",
                        data: "phone",
                        render: function (data) {
                            return `+${data}`;
                        }
                    },
                    {
                        title: t("admin.global.Address"),
                        title: "Address",
                        data: "address",
                        render: function (data, type, row, meta) {
                            if (data) {
                                return `<a href="https://maps.google.com?q=${data}" target="_blank" class="" style="color: black; text-decoration: underline;"> ${data} </a>`;
                            } else {
                                return "NA";
                            }
                        },
                    },
                    {
                        title: t("client.meeting.attender"),
                        title: "Meeting Attender",
                        data: "attender_name",
                    },
                    {
                        title: t("admin.global.Status"),
                        title: " Status",
                        data: "booking_status",
                        render: function (data, type, row, meta) {
                            let color = "";
                            if (data == "pending") {
                                color = "purple";
                            } else if (data == "confirmed" || data == "completed") {
                                color = "green";
                            } else {
                                color = "red";
                            }

                            // return `<span style="color: ${color};">${data}</span>`;
                            return `<p style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        ${data}
                                    </p>`;
                        },
                    },
                    {
                        title: t("admin.global.Action"),
                        title: "Action",
                        data: "action",
                        orderable: false,
                        responsivePriority: 1,
                        render: function (data, type, row, meta) {
                            let _html =
                                '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                            _html += `<button type="button" class="dropdown-item dt-view-btn" data-worker-id="${row.worker_id}" data-hid="${row.id}">Edit / View</button>`;

                            _html += `<button type="button" class="dropdown-item dt-claim-btn" data-worker-id="${row.worker_id}" data-hid="${row.id}">Create Claim</button>`;

                            _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">Delete</button>`;

                            _html += "</div> </div>";

                            return _html;
                        },
                    },
                ],
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                    $(row).attr("data-worker-id", data.worker_id);
                },
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class ');
                        },
                    },
                ],
            });
        }
    };

    useEffect(() => {
        initializeDataTable();
        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _workerID = $(this).data("worker-id");
            const _hid = $(this).data("hid"); // Get the hearing ID
            navigate(`/admin/workers/view/${_workerID}/hearing-invitation/${_hid}`);
        });

        $(tableRef.current).on("click", ".dt-claim-btn", function () {
            const _id = $(this).data("hid");
            toggleClaimModal(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true);
            }
        };
    }, []);

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Hearing!",
        }).then((result) => {
            if (result.isConfirmed) {
                setIsLoading(true);
                axios.delete(`/api/admin/hearing/${id}`, { headers })
                    .then(() => {
                        setIsLoading(false);
                        Swal.fire("Deleted!", "Hearing has been deleted.", "success");
                        $(tableRef.current).DataTable().ajax.reload();
                    })
                    .catch(() => {
                        setIsLoading(false);
                        Swal.fire("Error!", "There was an error deleting the hearing.", "error");
                    });
            }
        });
    };

    return (
        <div className="WorkersHearing">
            <div className="titleBox customer-title">
                <div className="row">
                    <div className="col-sm-6 d-flex align-items-center justify-content-between">
                        <h1 className="page-title">{t("admin.hearing.title")}</h1>
                    </div>
                    <div className="col-sm-6 d-flex align-items-center justify-content-end">
                        <button
                            className="text-white navyblue text-left mr-2 w-full"
                            style={{ padding: "5px", borderRadius: "5px" }}
                            onClick={handleScheduleHearing}
                        >
                            <i className="fas fa-hand-point-right "></i>
                            {t("admin.hearing.scheduleHearing")}
                        </button>
                        {/* <button 
                                className="text-white navyblue text-left mr-2 w-full"
                                style={{ padding: "5px", borderRadius: "5px" }}
                                onClick={handleCreateClaim}
                            >
                                <i className="fas fa-upload"></i>
                                Create Claim
                            </button> */}
                        {/* <button     
                                className="text-white navyblue text-left mr-2 w-full"
                                style={{ padding: "5px", borderRadius: "5px" }}
                                onClick={handleUploadClaim}
                            >
                                <i className="fas fa-upload"></i>
                                Upload Claim
                            </button> */}
                    </div>
                </div>
            </div>
            <div className=" mb-4 d-none d-lg-block">
                <div className="row">
                    <div
                        style={{
                            fontWeight: "bold",
                            marginTop: 10,
                            marginLeft: 15,
                        }}
                    >
                        {t("global.filter")}
                    </div>
                    <div>
                        <FilterButtons
                            text={t("admin.global.All")}
                            className="px-3 mr-1 ml-4"
                            selectedFilter={filter}
                            setselectedFilter={setFilter}
                        />
                        {meetingStatuses.map((_status, _index) => {
                            return (
                                <FilterButtons
                                    text={_status}
                                    className="mr-1 px-3 ml-2"
                                    key={_index}
                                    selectedFilter={filter}
                                    setselectedFilter={setFilter}
                                />
                            );
                        })}
                    </div>
                </div>
            </div>
            <div className="card " style={{ boxShadow: "none" }}>
                <div className="card-body pl-0 pr-0">
                    <div className="boxPanel">
                        <table
                            ref={tableRef}
                            className="display table table-bordered"
                        />
                    </div>
                </div>
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Send Message</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label htmlFor="claim">Claim Description</label>
                                <textarea
                                    id="claim"
                                    className="form-control"
                                    rows="5"
                                    value={claimText}
                                    onChange={(e) => setClaimText(e.target.value)}
                                    placeholder="Enter claim details here..."
                                    required                                    
                                />
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
                        onClick={handleClaimSubmit}
                        className="btn btn-primary"                                                     
                    >
                        {t("global.send")}
                    </Button>
                </Modal.Footer>
            </Modal>
            <FullPageLoader visible={isLoading} />
        </div>
    );
};

export default WorkersHearing;
