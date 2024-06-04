import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useNavigate } from "react-router-dom";
import { CSVLink } from "react-csv";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import Sidebar from "../../Layouts/Sidebar";
import { useTranslation } from "react-i18next";
import ChangeStatusModal from "../../Components/Modals/ChangeStatusModal";

export default function Clients() {
    const [clients, setClients] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [filter, setFilter] = useState("");
    const [loading, setLoading] = useState("Loading...");

    const [stat, setStat] = useState("null");
    const [show, setShow] = useState(false);
    const [importFile, setImportFile] = useState("");
    const [changeStatusModal, setChangeStatusModal] = useState({
        isOpen: false,
        id: 0,
    });

    const alert = useAlert();
    const { t } = useTranslation();

    const handleClose = () => {
        setImportFile("");
        setShow(false);
    };
    const handleShow = () => {
        setImportFile("");
        setShow(true);
    };

    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const formHeaders = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleImportSubmit = () => {
        const formData = new FormData();
        formData.append("file", importFile);
        axios
            .post("/api/admin/import-clients", formData, {
                headers: formHeaders,
            })
            .then((response) => {
                handleClose();
                if (response.data.error) {
                    alert.error(response.data.error);
                } else {
                    alert.success(response.data.message);
                    setTimeout(() => {
                        getclients();
                    }, 1000);
                }
            })
            .catch((error) => {
                handleClose();
                alert.error(error.message);
            });
    };

    const getclients = () => {
        axios.get("/api/admin/clients", { headers }).then((response) => {
            if (response.data.clients.data.length > 0) {
                setClients(response.data.clients.data);
                setPageCount(response.data.clients.last_page);
            } else {
                setLoading("No client found");
            }
        });
    };

    useEffect(() => {
        getclients();
    }, []);

    const filterClients = (e) => {
        axios
            .get(`/api/admin/clients?q=${e.target.value}`, { headers })
            .then((response) => {
                if (response.data.clients.data.length > 0) {
                    setClients(response.data.clients.data);
                    setPageCount(response.data.clients.last_page);
                } else {
                    setClients([]);
                    setPageCount(response.data.clients.last_page);
                    setLoading("No client found");
                }
            });
    };

    const filterClientsStat = (s) => {
        setFilter(s);
        axios.get(`/api/admin/clients?q=${s}`, { headers }).then((response) => {
            if (response.data.clients.data.length > 0) {
                setClients(response.data.clients.data);
                setPageCount(response.data.clients.last_page);
            } else {
                setClients([]);
                setPageCount(response.data.clients.last_page);
                setLoading("No client found");
            }
        });
    };

    const booknun = (s) => {
        setFilter(s);
        axios
            .get(`/api/admin/clients?action=${s}`, { headers })
            .then((response) => {
                if (response.data.clients.data.length > 0) {
                    setClients(response.data.clients.data);
                    setPageCount(response.data.clients.last_page);
                } else {
                    setClients([]);
                    setPageCount(response.data.clients.last_page);
                    setLoading("No client found");
                }
            });
    };

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        let cn =
            filter == "booked" || filter == "notbooked" ? "&action=" : "&q=";

        axios
            .get("/api/admin/clients?page=" + currentPage + cn + filter, {
                headers,
            })
            .then((response) => {
                if (response.data.clients.data.length > 0) {
                    setClients(response.data.clients.data);
                    setPageCount(response.data.clients.last_page);
                } else {
                    setLoading("No client found");
                }
            });
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Client!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/clients/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Client has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getclients();
                        }, 1000);
                    });
            }
        });
    };
    const handleNavigate = (e, id) => {
        e.preventDefault();
        navigate(`/admin/view-client/${id}`);
    };

    const copy = [...clients];
    const [order, setOrder] = useState("ASC");
    const sortTable = (e, col) => {
        let n = e.target.nodeName;
        if (n != "SELECT") {
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
        }

        if (order == "ASC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? 1 : -1
            );
            setClients(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setClients(sortData);
            setOrder("ASC");
        }
    };

    const [Alldata, setAllData] = useState([]);

    const handleReport = (e) => {
        e.preventDefault();

        let cn = stat == "booked" || stat == "notbooked" ? "action=" : "f=";

        axios
            .get("/api/admin/clients_export?" + cn + stat, { headers })
            .then((response) => {
                if (response.data.clients.length > 0) {
                    let r = response.data.clients;

                    if (r.length > 0) {
                        for (let k in r) {
                            delete r[k]["extra"];
                            delete r[k]["jobs"];
                        }
                    }
                    setAllData(r);
                    document.querySelector("#csv").click();
                } else {
                }
            });
    };

    const csvReport = {
        data: Alldata,
        filename: "clients",
    };

    const toggleChangeStatusModal = (clientId = 0) => {
        setChangeStatusModal((prev) => {
            return {
                isOpen: !prev.isOpen,
                id: clientId,
            };
        });
    };
    const updateData = () => {
        setTimeout(() => {
            getclients();
        }, 1000);
    };
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex flex-column flex-lg-row">
                        <div className="d-flex mt-2 d-lg-none justify-content-between">
                            <h1 className="page-title p-0">
                                {t("admin.sidebar.Clients")}
                            </h1>
                            <Link
                                to="/admin/clients/create"
                                className="btn btn-pink addButton"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                Add New
                            </Link>
                        </div>

                        <div className="d-flex w-100 justify-content-between align-items-center">
                            <h1 className="page-title d-none d-lg-block">
                                {t("admin.sidebar.Clients")}
                            </h1>
                            <div className="search-data">
                                <div
                                    className="App"
                                    style={{ display: "none" }}
                                >
                                    <CSVLink {...csvReport} id="csv">
                                        {t("admin.global.Export")}
                                    </CSVLink>
                                </div>
                                <div className="action-dropdown dropdown mt-4 mr-2">
                                    <button
                                        className="btn btn-pink"
                                        onClick={handleShow}
                                    >
                                        {t("admin.global.Import")}
                                    </button>
                                </div>
                                <div className="action-dropdown dropdown mt-4 mr-2 d-none d-lg-block">
                                    <button
                                        className="btn btn-pink ml-2"
                                        onClick={(e) => handleReport(e)}
                                    >
                                        {t("admin.client.Export")}
                                    </button>
                                </div>

                                <div className="action-dropdown dropdown mt-4 mr-2 d-lg-none">
                                    <button
                                        type="button"
                                        className="btn btn-default dropdown-toggle"
                                        data-toggle="dropdown"
                                    >
                                        <i className="fa fa-filter"></i>
                                    </button>
                                    <div className="dropdown-menu dropdown-menu-right">
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setStat("null");
                                                getclients();
                                            }}
                                        >
                                            {t("admin.global.All")}
                                        </button>
                                        {/* <button className="dropdown-item" onClick={(e)=>{setStat(0);filterClientsStat('lead')}}>Lead</button>
                                    <button className="dropdown-item" onClick={(e)=>{setStat(2);filterClientsStat('customer')}}>Customer</button>
                                    <button className="dropdown-item" onClick={(e)=>{setStat(1);filterClientsStat('potential customer')}}>Potential Customer</button> */}
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setStat("booked");
                                                booknun("booked");
                                            }}
                                        >
                                            {t("admin.client.BookedCustomer")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setStat("notbooked");
                                                booknun("notbooked");
                                            }}
                                        >
                                            {t(
                                                "admin.client.NotBookedCustomer"
                                            )}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => handleReport(e)}
                                        >
                                            {t("admin.client.Export")}
                                        </button>
                                    </div>
                                </div>

                                <input
                                    type="text"
                                    className="form-control action-dropdown dropdown mt-4 mr-2"
                                    onChange={(e) => {
                                        filterClients(e);
                                        setFilter(e.target.value);
                                    }}
                                    placeholder="Search"
                                />
                                <Link
                                    to="/admin/clients/create"
                                    className="btn btn-pink addButton d-none d-lg-block  action-dropdown dropdown mt-4 mr-2"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    Add New
                                </Link>
                            </div>
                        </div>
                        <div className="hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e, e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="id">ID</option>
                                <option value="firstname">Name</option>
                                {/* <option value="address">Address</option> */}
                                <option value="email">Email</option>
                                <option value="phone">Phone</option>
                                {/* <option value="status">Status</option> */}
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row mb-2 d-none d-lg-block">
                    <div className="col-sm-12 d-flex align-items-center">
                        <div className="mr-3" style={{ fontWeight: "bold" }}>
                            Status
                        </div>
                        <FilterButtons
                            text={t("admin.global.All")}
                            className="px-3 mr-1"
                            value="null"
                            onClick={() => {
                                setStat("null");
                                getclients();
                            }}
                            selectedFilter={stat}
                        />

                        <FilterButtons
                            text={t("admin.client.BookedCustomer")}
                            value="booked"
                            className="px-3 mr-1"
                            onClick={() => {
                                setStat("booked");
                                booknun("booked");
                            }}
                            selectedFilter={stat}
                        />
                        <FilterButtons
                            text={t("admin.client.NotBookedCustomer")}
                            className="px-3 mr-1"
                            value="notbooked"
                            onClick={() => {
                                setStat("notbooked");
                                booknun("notbooked");
                            }}
                            selectedFilter={stat}
                        />
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            {clients.length > 0 ? (
                                <Table className="table table-bordered">
                                    <Thead>
                                        <Tr style={{ cursor: "pointer" }}>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "id");
                                                }}
                                            >
                                                ID{" "}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "firstname");
                                                }}
                                            >
                                                Name{" "}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "email");
                                                }}
                                            >
                                                Email{" "}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            {/* <Th
                                                onClick={(e) => {
                                                    sortTable(e, "address");
                                                }}
                                            >
                                                Address{" "}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th> */}
                                            <Th
                                                onClick={(e) => {
                                                    sortTable(e, "phone");
                                                }}
                                            >
                                                Phone{" "}
                                                <span className="arr">
                                                    {" "}
                                                    &darr;{" "}
                                                </span>
                                            </Th>
                                            <Th>Status</Th>
                                            <Th>Action</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {clients &&
                                            clients.map((item, index) => {
                                                // let address = item.geo_address
                                                //     ? item.geo_address
                                                //     : "NA";
                                                // let cords =
                                                //     item.latitude &&
                                                //     item.longitude
                                                //         ? item.latitude +
                                                //           "," +
                                                //           item.longitude
                                                //         : "";
                                                let status = "";
                                                // if (item.status == 0)
                                                //     status = "Lead";
                                                // if (item.status == 1)
                                                //     status = "Potential Customer";
                                                // if (item.status == 2)
                                                //     status = "Customer";

                                                let phone = item.phone
                                                    ? item.phone
                                                          .toString()
                                                          .split(",")
                                                    : [];

                                                return (
                                                    <Tr
                                                        style={{
                                                            cursor: "pointer",
                                                        }}
                                                        key={index}
                                                    >
                                                        <Td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            {item.id}
                                                        </Td>
                                                        <Td>
                                                            <Link
                                                                to={`/admin/view-client/${item.id}`}
                                                            >
                                                                {item.firstname}{" "}
                                                                {item.lastname}
                                                            </Link>
                                                        </Td>
                                                        <Td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            {item.email}
                                                        </Td>
                                                        {/* <Td>
                                                            <a
                                                                href={`https://maps.google.com?q=${cords}`}
                                                                target="_blank"
                                                            >
                                                                {address}
                                                            </a>
                                                        </Td> */}
                                                        {/*<Td><a  href={`tel:${item.phone.toString().split(",").join(' | ')}`}>{(item.phone) ? item.phone.toString().split(",").join(' | ') : ''}</a></Td>*/}

                                                        <Td>
                                                            {phone &&
                                                                phone.map(
                                                                    (p, i) => {
                                                                        return (
                                                                            <a
                                                                                href={`tel:${p}`}
                                                                                key={
                                                                                    i
                                                                                }
                                                                            >
                                                                                {phone.length >
                                                                                1
                                                                                    ? p
                                                                                    : p}{" "}
                                                                                |{" "}
                                                                            </a>
                                                                        );
                                                                    }
                                                                )}
                                                        </Td>

                                                        <Td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            {item.lead_status
                                                                ? item
                                                                      .lead_status
                                                                      .lead_status
                                                                : "NA"}
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
                                                                    {item.latest_contract !=
                                                                        0 && (
                                                                        <Link
                                                                            to={`/admin/create-job/${item.latest_contract}`}
                                                                            className="dropdown-item"
                                                                        >
                                                                            Create
                                                                            Job
                                                                        </Link>
                                                                    )}

                                                                    <Link
                                                                        to={`/admin/clients/${item.id}/edit`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        Edit
                                                                    </Link>
                                                                    <Link
                                                                        to={`/admin/view-client/${item.id}`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        View
                                                                    </Link>
                                                                    <button
                                                                        className="dropdown-item"
                                                                        onClick={() =>
                                                                            toggleChangeStatusModal(
                                                                                item.id
                                                                            )
                                                                        }
                                                                    >
                                                                        Change
                                                                        status
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
                                                );
                                            })}
                                    </Tbody>
                                </Table>
                            ) : (
                                <p className="text-center mt-5">{loading}</p>
                            )}
                            {clients.length > 0 ? (
                                <ReactPaginate
                                    previousLabel={"Previous"}
                                    nextLabel={"Next"}
                                    breakLabel={"..."}
                                    pageCount={pageCount}
                                    marginPagesDisplayed={2}
                                    pageRangeDisplayed={3}
                                    onPageChange={handlePageClick}
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
            <Modal show={show} onHide={handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Import File</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <a href="/api/admin/clients-sample-file">
                        Download sample file
                    </a>
                    <form encType="multipart/form-data">
                        <div className="row mt-2">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <input
                                        type="file"
                                        onChange={(e) =>
                                            setImportFile(e.target.files[0])
                                        }
                                        className="form-control"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    </form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>
                        Close
                    </Button>
                    <Button
                        className="btn btn-pink"
                        onClick={handleImportSubmit}
                    >
                        Submit
                    </Button>
                </Modal.Footer>
            </Modal>
            {changeStatusModal.isOpen && (
                <ChangeStatusModal
                    handleChangeStatusModalClose={toggleChangeStatusModal}
                    isOpen={changeStatusModal.isOpen}
                    clientId={changeStatusModal.id}
                    getUpdatedData={updateData}
                />
            )}
        </div>
    );
}
const FilterButtons = ({ text, className, selectedFilter, onClick, value }) => (
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === value
                ? { background: "white" }
                : {
                      background: "#2c3f51",
                      color: "white",
                  }
        }
        onClick={() => {
            onClick?.();
        }}
    >
        {text}
    </button>
);
