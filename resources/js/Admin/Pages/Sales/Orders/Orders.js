import React, { useState, useEffect } from "react";
import Sidebar from "../../../Layouts/Sidebar";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from 'react-super-responsive-table'
import { Link } from "react-router-dom";
import axios from "axios";
import Moment from 'moment';
import { Base64 } from "js-base64";

export default function Orders() {

    const [loading, setLoading] = useState("Loading...");
    const [pageCount, setPageCount] = useState(0);
    const [orders, setOrders]   = useState([]);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getOrders = () => {
        axios
            .get('/api/admin/orders', { headers })
            .then((res) => {
                if (res.data.orders.data.length > 0) {
                    setOrders(res.data.orders.data);
                    setPageCount(res.data.orders.last_page);
                } else {
                    setOrders([]);
                    setLoading('No Order Found');
                }
            })
    }

    const copy = [...orders];
    const [order,setOrder] = useState('ASC');
    const sortTable = (e,col) =>{
        
        let n = e.target.nodeName;
        if(n != "SELECT"){
        if (n == "TH") {
            let q = e.target.querySelector('span');
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
 
        if(order == 'ASC'){
            const sortData = [...copy].sort((a, b) => (a[col] < b[col] ? 1 : -1));
            setOrders(sortData);
            setOrder('DESC');
        }
        if(order == 'DESC'){
            const sortData = [...copy].sort((a, b) => (a[col] < b[col] ? -1 : 1));
            setOrders(sortData);
            setOrder('ASC');
        }
        
    }

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Order!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .get(`/api/admin/delete-oders/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Order has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getOrders();
                        }, 1000);
                    });
            }
        });
    };

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        axios
            .get("/api/admin/orders?page=" + currentPage, { headers })
            .then((response) => {
                if (response.data.orders.data.length > 0) {
                    setOrders(response.data.orders.data);
                    setPageCount(response.data.orders.last_page);
                } else {
                    setLoading("No Order Found");
                }
            });
    };

    useEffect(() => {
        getOrders();
    }, []);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <h1 className="page-title">Manage Orders</h1>
                </div>
                <div className="sales-filter">
                    <div className="row">
                        <div className="col-sm-2 col-6">
                            <div className="form-group">
                                <label className="control-label">From Date</label>
                                <input type="date" className="form-control" />
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <div className="form-group">
                                <label className="control-label">To Date</label>
                                <input type="date" className="form-control" />
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <div className="form-group">
                                <label className="control-label">Order ID</label>
                                <input type="text" className="form-control" placeholder="Order ID" />
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <div className="form-group">
                                <label className="control-label">Customer</label>
                                <input type="text" className="form-control" placeholder="Customer" />
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <div className="form-group">
                                <label className="control-label">Status</label>
                                <select className="form-control">
                                    <option>Please Select</option>
                                    <option>Open</option>
                                    <option>Close</option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <label className="control-label d-block">&nbsp;</label>
                            <button className="btn btn-pink" style={{minWidth: "100px"}}>Filter</button>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                            { orders.length > 0 ?(
                                <Table className="table table-bordered">
                                    <Thead>
                                        <Tr>
                                            <Th scope="col" style={{ cursor: "pointer" }} onClick={(e)=>{sortTable(e,'id')}}  > #Order ID           <span className="arr"> &darr;</span></Th>
                                            <Th scope="col" >Job </Th>
                                            <Th scope="col" style={{ cursor: "pointer" }} onClick={(e)=>{sortTable(e,'created_at')}} >Created Date   <span className="arr"> &darr;</span></Th>
                                            <Th scope="col"  >Customer   </Th>
                                            <Th scope="col" style={{ cursor: "pointer" }}  onClick={(e)=>{sortTable(e,'status')}}>Status            <span className="arr"> &darr;</span></Th>
                                            <Th scope="col">Invoice Status</Th>
                                            <Th scope="col">Action</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {orders &&
                                            orders.map((item, index) => {
                                                let services = (item.items != undefined && item.items != null) ? JSON.parse(item.items) : []

                                                return (
                                                    <Tr>
                                                        <Td>#{item.order_id}</Td>
                                                        <Td><Link to={`/admin/view-job/${item.job.id}`}>{ Moment(item.job.start_date).format('DD-MM-Y')+ " | "+item.job.shifts  }</Link></Td>
                                                        <Td>{ Moment(item.created_at).format('DD, MMM Y')}</Td>
                                                        <Td><Link to={`/admin/view-client/${item.client.id}`}>{item.client.firstname + " " + item.client.lastname}</Link></Td>
                                                        <Td>
                                                            {item.status}
                                                        </Td>
                                                        <Td>
                                                            { item.invoice_status == 0 ? "Not Generated": "Generated" }
                                                        </Td>
                                                        <Td>
                                                            <div className="action-dropdown dropdown">
                                                                <button type="button" className="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>
                                                                <div className="dropdown-menu">
                                                                    <a target="_blank" href={item.doc_url} className="dropdown-item">View Order</a>
                                                                    <button  onClick={e=>handleDelete(item.id)} className="dropdown-item"
                                                                    >Delete</button>
                                                                </div>
                                                            </div>
                                                        </Td>
                                                    </Tr>
                                                )
                                            })}
                                    </Tbody>
                                </Table>)
                                :(
                                    <div className="form-control text-center"> No Order Found</div>
                                )}

                               {orders.length > 0 ? (
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
                            ) : ''}


                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    )
}