import React, { useState, useEffect } from "react";
import axios from "axios";
import { useParams, Link } from "react-router-dom";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import ReactPaginate from "react-paginate";

export default function ScheduledMeeting() {
    const [schedules, setSchedules] = useState([]);
    const [loading, setLoading] = useState("Loading..");
    const [currentPage, setCurrentPage] = useState(0);
    const param = useParams();
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const PER_PAGE = 4;

    const getSchedules = () => {
        axios
            .post(`/api/admin/client-schedules`, { id: param.id }, { headers })
            .then((res) => {
                if (res.data.schedules.length > 0) {
                    setSchedules(res.data.schedules);
                    console.log("meeting data is:", res.data.schedules);
                    
                } else {
                    setLoading("No meeting scheduled yet.");
                }
            });
    };

    useEffect(() => {
        getSchedules();
    }, []);

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Meeting!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/schedule/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Meeting has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getSchedules();
                        }, 1000);
                    })
                    .catch((e) => {
                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
            }
        });
    };

    const handlePageClick = ({ selected }) => {
        setCurrentPage(selected);
    };

    const offset = currentPage * PER_PAGE;
    const currentPageData = schedules.slice(offset, offset + PER_PAGE);
    const pageCount = Math.ceil(schedules.length / PER_PAGE);

    return (
        <div className="">
            <div className="table-responsive">
                {schedules.length > 0 ? (
                    <>
                        <table className="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{t("admin.leads.viewLead.ID")}</th>
                                    <th>{t("admin.leads.viewLead.MeetingAttende")}</th>
                                    <th>{t("admin.leads.viewLead.Scheduled")}</th>
                                    <th>{t("admin.leads.viewLead.Status")}</th>
                                    <th>{t("admin.leads.viewLead.Action")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {currentPageData.map((item, i) => {
                                    let color = "";
                                    if (item.booking_status === "pending") {
                                        color = "purple";
                                    } else if (
                                        item.booking_status === "confirmed" ||
                                        item.booking_status === "completed"
                                    ) {
                                        color = "green";
                                    } else {
                                        color = "red";
                                    }

                                    return (
                                        <tr key={i}>
                                            <td>#{item.id}</td>
                                            <td>{item.team ? item.team.name : "NA"}</td>
                                            <td>
                                                {Moment(item.meet_via === "off-site" ? "N\A" : item.start_date).format("DD/MM/Y")}
                                                <br />
                                                {Moment(item.meet_via === "off-site" ? "N\A" : item.start_date).format("dddd")}
                                                {item.start_time && item.end_time && (
                                                    <>
                                                        <br />
                                                        {"Start : " + item.start_time}
                                                        <br />
                                                        {"End : " + item.end_time}
                                                    </>
                                                )}
                                            </td>
                                            <td><span style={{backgroundColor: "#2F4054", color: "white", padding: "5px 10px", borderRadius: "5px", width: "110px", textAlign: "center"}}>{item.booking_status}</span></td>
                                            <td>
                                                <div className="d-flex">
                                                    <Link
                                                        to={`/admin/schedule/view/${param.id}?sid=${item.id}`}
                                                        className="btn"
                                                        style={{fontSize: "15px", color: "#2F4054", padding: "5px 8px", background: "#E5EBF1", borderRadius: "5px"}}
                                                    >
                                                        <i className="fa fa-eye"></i>
                                                    </Link>
                                                    <button
                                                        className="ml-2 btn"
                                                        style={{fontSize: "15px", color: "#2F4054", padding: "5px 8px", background: "#E5EBF1", borderRadius: "5px"}}
                                                        onClick={() => handleDelete(item.id)}
                                                    >
                                                        <i className="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                        <ReactPaginate
                            previousLabel={"← Previous"}
                            nextLabel={"Next →"}
                            pageCount={pageCount}
                            onPageChange={handlePageClick}
                            containerClassName={"pagination"}
                            previousLinkClassName={"pagination__link"}
                            nextLinkClassName={"pagination__link"}
                            disabledClassName={"pagination__link--disabled"}
                            activeClassName={"pagination__link--active"}
                            pageClassName={"pagination__item"} // Added this line
                            pageLinkClassName={"pagination__link"} // Added this line
                        />

                    </>
                ) : (
                    <div className="form-control text-center">{loading}</div>
                )}
            </div>
        </div>
    );
}
