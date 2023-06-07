import axios from 'axios';
import React,{useState,useEffect} from 'react'
import { Link } from "react-router-dom";
import { useParams } from 'react-router-dom';
import Moment from 'moment';

export default function Jobs() {
    
    const [jobs,setJobs] = useState([]);
    const [loading , setLoading] = useState("Loading...");
    const params = useParams();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const getJobs = () =>{
        axios
        .post(`/api/admin/get-client-jobs`,{cid:params.id},{headers})
        .then((res)=>{
            (res.data.jobs.length >0) ?
            setJobs(res.data.jobs)
            :setLoading('No job found');
        });
    }

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Job!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/jobs/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Job has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getJobs();
                        }, 1000);
                    });
            }
        });
    };
    
    useEffect(()=>{
       getJobs();
    },[]);

    const copy = [...jobs];
    const [order,setOrder] = useState('ASC');
    const sortTable = (e,col) =>{
        
        let n = e.target.nodeName;

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

        if(order == 'ASC'){
            const sortData = [...copy].sort((a, b) => (a[col] < b[col] ? 1 : -1));
            setJobs(sortData);
            setOrder('DESC');
        }
        if(order == 'DESC'){
            const sortData = [...copy].sort((a, b) => (a[col] < b[col] ? -1 : 1));
            setJobs(sortData);
            setOrder('ASC');
        }
        
    }

  return (
    <div className="boxPanel">
        <div className="table-responsive">
            { jobs.length >0 ? (
            <table className="table table-bordered">
                <thead>
                    <tr>
                        <th onClick={(e)=>sortTable(e,'id')} style={{cursor:'pointer'}}>ID <span className='arr'> &darr; </span></th>
                        <th>Service Name</th>
                        <th>Worker Name</th>
                        <th>Total Price</th>
                        <th onClick={(e)=>sortTable(e,'created_at')} style={{cursor:'pointer'}}>Date Created <span className='arr'> &darr; </span></th>
                        <th onClick={(e)=>sortTable(e,'status')} style={{cursor:'pointer'}}>Status <span className='arr'> &darr; </span></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>       
                    { jobs && jobs.map((j,i)=>{
                       // let services = (j.offer.services) ? JSON.parse(j.offer.services) : [];
                        let total = 0;
                      
                        return(
                        <tr>
                            <td>#{j.id}</td>
                            <td>
                                {
                                    
                                    j.jobservice && j.jobservice.map((js,i)=>{
                                     
                                         total += parseInt(js.total);
                                       return (js.name)? js.name+" ":'NA'
                                    })
                                }
                             
                            </td>
                            <td>{
                               j.worker 
                                ? j.worker.firstname 
                                +" "+ j.worker.lastname
                                :"NA" 
                             }</td>
                            <td> {total } ILS + VAT</td>
                            <td>{Moment(j.created_at).format('DD MMM,Y')}</td>
                            <td>{j.status}</td>
                           {/* <td>
                                <div className="d-flex">
                                { (j.worker) ? 
                                    <Link to={`/admin/edit-job/${j.id}`} className="btn bg-purple"><i className="fa fa-edit"></i></Link>
                                    :<Link to={`/admin/create-job/${j.contract_id}`} className="btn bg-purple"><i className="fa fa-edit"></i></Link>
                                }
                                    <Link to={`/admin/view-job/${j.id}`} className="ml-2 btn bg-yellow"><i className="fa fa-eye"></i></Link>

                                    <Link to={`/admin/add-order/?j=${j.id}&c=${params.id}`} className="ml-2 btn bg-yellow"><i className="fa fa-circle"></i></Link>
                                    <Link to={`/admin/add-invoice/?j=${j.id}&c=${params.id}`} className="ml-2 btn bg-yellow"><i className="fa fa-triangle"></i></Link>

                                    <button className="ml-2 btn bg-red" onClick={() => handleDelete( j.id )}><i className="fa fa-trash"></i></button>                            
                                </div>
                            </td>*/}
                             <td className='text-center'>

                                <div className="action-dropdown dropdown pb-2">
                                    <button type="button" className="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <i className="fa fa-ellipsis-vertical"></i>
                                    </button>
                                    
                                    <div className="dropdown-menu">
                                    { (!j.worker) && 
                                    <Link to={`/admin/create-job/${j.contract_id}`}  className="dropdown-item" >Create Job</Link>
                                    }
                                     <Link to={`/admin/view-job/${j.id}`}  className="dropdown-item" >View Job</Link>

                                    <Link to={`/admin/add-order/?j=${j.id}&c=${params.id}`}  className="dropdown-item" >Create Order</Link>
                                    <Link to={`/admin/add-invoice/?j=${j.id}&c=${params.id}`}  className="dropdown-item" >Create Invoice</Link>

                                    <button  className="dropdown-item" onClick={() => handleDelete( j.id )}>Delete</button>                            
                                    </div>
                                </div>
                               
                            </td>
                        </tr>    
                        )
                    })}
                      
                </tbody>
            </table>
            )
            :
            (
                <div className='form-control text-center'>{loading}</div>
            )
            }
        </div>
    </div>
  )
}
