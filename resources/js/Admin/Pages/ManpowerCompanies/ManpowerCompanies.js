import React, { useState, useEffect } from "react";
import axios from "axios";
import ReactPaginate from "react-paginate";
import { Link } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Swal from "sweetalert2";

import Sidebar from "../../Layouts/Sidebar";
import ManpowerCompanyModal from "../../Components/Modals/ManpowerCompanyModal";

export default function ManpowerCompanies() {
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [currentPage, setCurrentPage] = useState(0);
    const [isOpenCompanyModal, setIsOpenCompanyModal] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getManpowerCompanies = async () => {
        await axios
            .get("/api/admin/manpower-companies", {
                headers,
                params: {
                    page: currentPage,
                },
            })
            .then((response) => {
                if (response.data.companies.data.length > 0) {
                    setManpowerCompanies(response.data.companies.data);
                    setPageCount(response.data.companies.last_page);
                } else {
                    setManpowerCompanies([]);
                    setLoading("No company found");
                }
            });
    };

    useEffect(() => {
        getManpowerCompanies();
    }, [currentPage]);

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Company!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/manpower-companies/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Company has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getManpowerCompanies();
                        }, 1000);
                    });
            }
        });
    };
    const copy = [...manpowerCompanies];
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
            setManpowerCompanies(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setManpowerCompanies(sortData);
            setOrder("ASC");
        }
    };

    const handleAddCompany = () => {
        setIsOpenCompanyModal(true);
        setSelectedCompany(null);
    };

    const handleEditCompany = (_company) => {
        setIsOpenCompanyModal(true);
        setSelectedCompany(_company);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Manpower Companies</h1>
                        </div>
                        <div className="col-sm-6">
                            <button
                                type="button"
                                className="ml-2 btn btn-success addButton"
                                onClick={handleAddCompany}
                            >
                                Add Manpower Company
                            </button>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {manpowerCompanies.length > 0 ? (
                                    <Table className="table table-bordered">
                                        <Thead>
                                            <Tr>
                                                <Th
                                                    scope="col"
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "id");
                                                    }}
                                                >
                                                    ID{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    scope="col"
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "name");
                                                    }}
                                                >
                                                    Name
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th scope="col">Contract</Th>
                                                <Th scope="col">Action</Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {manpowerCompanies.map(
                                                (item, index) => (
                                                    <Tr key={index}>
                                                        <Td>{item.id}</Td>
                                                        <Td>{item.name}</Td>
                                                        <Td>
                                                            {item.contract_filename && (
                                                                <a
                                                                    href={`/storage/manpower-companies/contract/${item.contract_filename}`}
                                                                    target="_blank"
                                                                >
                                                                    <i className="fa fa-eye"></i>
                                                                </a>
                                                            )}
                                                        </Td>
                                                        <Td>
                                                            <div className="action-dropdown dropdown">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-default dropdown-toggle"
                                                                    data-toggle="dropdown"
                                                                >
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>
                                                                <div className="dropdown-menu">
                                                                    <button
                                                                        onClick={() =>
                                                                            handleEditCompany(
                                                                                item
                                                                            )
                                                                        }
                                                                        className="dropdown-item"
                                                                    >
                                                                        Edit
                                                                    </button>
                                                                    <button
                                                                        className="dropdown-item"
                                                                        onClick={() =>
                                                                            handleDelete(
                                                                                item.id
                                                                            )
                                                                        }
                                                                    >
                                                                        Delete
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </Td>
                                                    </Tr>
                                                )
                                            )}
                                        </Tbody>
                                    </Table>
                                ) : (
                                    <p className="text-center mt-5">
                                        {loading}
                                    </p>
                                )}
                                {manpowerCompanies.length > 0 ? (
                                    <ReactPaginate
                                        previousLabel={"Previous"}
                                        nextLabel={"Next"}
                                        breakLabel={"..."}
                                        pageCount={pageCount}
                                        marginPagesDisplayed={2}
                                        pageRangeDisplayed={3}
                                        onPageChange={(data) => {
                                            setCurrentPage(data.selected + 1);
                                        }}
                                        containerClassName={
                                            "pagination justify-content-end mt-3"
                                        }
                                        pageClassName={"page-item"}
                                        pageLinkClassName={"page-link"}
                                        previousClassName={"page-item"}
                                        previousLinkClassName={"page-link"}
                                        nextClassName={"page-item"}
                                        nextLinkClassName={"page-link"}
                                        breakClassName={"page-item"}
                                        breakLinkClassName={"page-link"}
                                        activeClassName={"active"}
                                    />
                                ) : (
                                    <></>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {isOpenCompanyModal && (
                <ManpowerCompanyModal
                    isOpen={isOpenCompanyModal}
                    setIsOpen={setIsOpenCompanyModal}
                    company={selectedCompany}
                    onSuccess={() => {
                        setIsOpenCompanyModal(false);
                        getManpowerCompanies();
                    }}
                />
            )}
        </div>
    );
}
