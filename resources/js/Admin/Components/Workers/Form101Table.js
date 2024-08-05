import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Base64 } from "js-base64";
import Moment from "moment";

export default function Form101Table({ formdata, workerId }) {
    const [formData, setFormData] = useState([]);
    const [order, setOrder] = useState("ASC");

    useEffect(() => {
        if (!formData.length) {
            const form101Foms = formdata.filter((f) =>
                f.type.includes("form101")
            );
            setFormData(form101Foms);
        }
    }, [formdata]);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const copy = [...formData];
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
            setFormData(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setFormData(sortData);
            setOrder("ASC");
        }
    };

    return (
        <div className="boxPanel">
            <div className="boxPanel">
                <div className="table-responsive">
                    <table className="table table-bordered">
                        <thead>
                            <tr>
                                <th
                                    onClick={(e) => sortTable(e, "id")}
                                    style={{ cursor: "pointer" }}
                                >
                                    ID <span className="arr"> &darr; </span>
                                </th>
                                <th
                                    onClick={(e) =>
                                        sortTable(e, "submitted_at")
                                    }
                                    style={{ cursor: "pointer" }}
                                >
                                    {" "}
                                    Submitted at{" "}
                                    <span className="arr"> &darr; </span>
                                </th>
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
                            {formData.map((item, index) => {
                                return (
                                    <tr key={item.id}>
                                        <td>{item.id}</td>
                                        <td>
                                            {item.submitted_at
                                                ? Moment(
                                                      item.submitted_at
                                                  ).format("MMMM DD, Y")
                                                : "-"}
                                        </td>
                                        <td
                                            style={{
                                                textTransform: "capitalize",
                                            }}
                                        >
                                            {item.pdf_name
                                                ? "Signed"
                                                : "Not signed"}
                                        </td>
                                        <td>
                                            <div className="d-flex">
                                                <Link
                                                    target="_blank"
                                                    to={`/form101/${Base64.encode(
                                                        workerId.toString()
                                                    )}/${Base64.encode(
                                                        item.id.toString()
                                                    )}`}
                                                    className="ml-2 btn btn-warning"
                                                >
                                                    <i className="fa fa-eye"></i>
                                                </Link>
                                                {
                                                    item.pdf_name ? (
                                                        <Link
                                                        target="_blank"
                                                        to={`/storage/signed-docs/${item.pdf_name}`}
                                                        className="ml-2 btn btn-warning"
                                                    >
                                                        <i class="fa-solid fa-download"></i>
                                                    </Link>
                                                    ) : ''
                                                }
                                    
                                                
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                    {!formData.length && (
                        <p className="text-center mt-5">
                            No form101 data found
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
