import React, { useState } from "react";
import { Link } from "react-router-dom";
import Moment from "moment";
import Swal from "sweetalert2";
import ContractFileModal from "./ContractFileModal";
import { useAlert } from "react-alert";

export default function Contract({ contracts, setContracts, fetchContract }) {
    const [isOpenContractFileModal, setIsOpenContractFileModal] =
        useState(false);
    const [contractId, setContractId] = useState(0);
    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Contract!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/contract/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Contract has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            window.location.reload(true);
                        }, 1000);
                    });
            }
        });
    };

    const copy = [...contracts];
    const [order, setOrder] = useState("ASC");
    const sortTable = (e, col) => {
        let n = e.target.nodeName;

        if (n == "TH") {
            let q = e.target.querySelector("span");
            if (q.innerHTML === "↑") {
                q.innerHTML = "↓";
            } else {
                q.innerHTML = "↑";
            }
        } else {
            let q = e.target;
            if (q.innerHTML === "↑") {
                q.innerHTML = "↓";
            } else {
                q.innerHTML = "↑";
            }
        }

        if (order == "ASC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? 1 : -1
            );
            setContracts(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setContracts(sortData);
            setOrder("ASC");
        }
    };
    const handleContractFileSubmit = (formData) => {
        save(formData);
    };
    const save = (data) => {
        const header = {
            Accept: "application/json, text/plain, */*",
            "Content-Type": "multipart/form-data",
            Authorization: `Bearer ` + localStorage.getItem("admin-token"),
        };
        axios
            .post(`/api/admin/contract-file/save`, data, { headers: header })
            .then((res) => {
                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e][0]);
                    }
                } else {
                    alert.success(res.data.message);
                    fetchContract();
                    setIsOpenContractFileModal(false);
                }
            })
            .catch((err) => {
                alert.error("Error!");
            });
    };
    const handleToggleModal = (id) => {
        setIsOpenContractFileModal((prev) => !prev);
        setContractId(id);
    };
    return (
        <div className="boxPanel">
            <div className="table-responsive">
                {contracts.length > 0 ? (
                    <table className="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Service Name</th>
                                <th>Total Price</th>
                                <th>Date Created</th>
                                <th
                                    onClick={(e) => sortTable(e, "status")}
                                    style={{ cursor: "pointer" }}
                                >
                                    Status <span className="arr"> &darr; </span>
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contracts &&
                                contracts.map((c, i) => {
                                    let services = c.offer
                                        ? JSON.parse(c.offer.services)
                                        : [];

                                    let color = "";
                                    if (
                                        c.status == "un-verified" ||
                                        c.status == "not-signed"
                                    ) {
                                        color = "purple";
                                    } else if (c.status == "verified") {
                                        color = "green";
                                    } else {
                                        color = "red";
                                    }

                                    return (
                                        <tr key={i}>
                                            <td>#{c.id}</td>
                                            <td>
                                                {services &&
                                                    services.map((s, j) => {
                                                        return services.length -
                                                            1 !=
                                                            j
                                                            ? s.template == "others"
                                                                ? s.other_title +
                                                                  " | "
                                                                : s.name + " | "
                                                            : s.name;
                                                    })}
                                            </td>
                                            <td>
                                                {c.offer
                                                    ? c.offer.subtotal
                                                    : "NA"}{" "}
                                                ILS + VAT
                                            </td>
                                            <td>
                                                {Moment(c.created_at).format(
                                                    "MMMM DD, Y"
                                                )}
                                            </td>
                                            <td style={{ color }}>
                                                {c.status}
                                            </td>
                                            <td>
                                                <div className="d-flex">
                                                    {c.status == "verified" && (
                                                        <Link
                                                            to={`/admin/create-job/${c.id}`}
                                                            className="btn bg-success mr-2"
                                                        >
                                                            <i className="fa fa-plus"></i>
                                                        </Link>
                                                    )}
                                                    {c.signature === null &&
                                                    c.status === "verified" &&
                                                    c.file !== null ? (
                                                        <Link
                                                            to={`/storage/uploads/client/contract/${c.file}`}
                                                            className="btn bg-yellow"
                                                            target={"_blank"}
                                                        >
                                                            <i className="fa fa-eye"></i>
                                                        </Link>
                                                    ) : (
                                                        <Link
                                                            to={`/admin/view-contract/${c.id}`}
                                                            className="btn bg-yellow"
                                                        >
                                                            <i className="fa fa-eye"></i>
                                                        </Link>
                                                    )}
                                                    <button
                                                        className="ml-2 btn bg-red"
                                                        onClick={() =>
                                                            handleDelete(c.id)
                                                        }
                                                    >
                                                        <i className="fa fa-trash"></i>
                                                    </button>
                                                    {c.signature === null &&
                                                        c.status ===
                                                            "verified" && (
                                                            <button
                                                                className="ml-2 btn bg-blue"
                                                                onClick={() =>
                                                                    handleToggleModal(
                                                                        c.id
                                                                    )
                                                                }
                                                            >
                                                                <i className="fa fa-upload"></i>
                                                            </button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                        </tbody>
                    </table>
                ) : (
                    <div className="form-control text-center">
                        No record found
                    </div>
                )}
            </div>
            {isOpenContractFileModal && (
                <ContractFileModal
                    isOpen={isOpenContractFileModal}
                    setIsOpen={setIsOpenContractFileModal}
                    contractId={contractId}
                    handleContractSubmit={handleContractFileSubmit}
                />
            )}
        </div>
    );
}
