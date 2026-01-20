<template>
	<view class="container">
		<block v-if="isload">
			<view class="top" :style="{background:'linear-gradient('+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
			</view>
			<view class="content">
				<form @submit="formSubmit">
					<view class="tips-top">创建定制参数，加速加工眼镜</view>
					<view class="box">
						<view class="form-item dangan">
							<view class="form-label">定制参数</view>
							<view class="form-value name-item bb">
								<input type="text" name="name" placeholder="请输入定制参数名称" :value="detail.name" placeholder-style="font-size:28rpx;color:#cccccc" />
							</view>
						</view>
					</view>
					<!-- 定制参数 -->
					<view class="box">
						<view class="form-item table-header">
							<view class="form-label" style="text-decoration: underline;" @tap="synchronous">验光数据</view>
							<view class="form-value">
								<view class="table-item">R</view>
								<view class="table-item">L</view>
							</view>
						</view>
						<view class="table-body">
							<view class="form-item">
								<view class="form-label">球镜(SPH)</view>
								<view class="form-value">
									<view class="dushu">
										<view class="unit-item">
											<view style="width: 100%;">
												<view :class="formdata.sph_right?'':'hui'" class="flex-e" data-field="sph_right" data-type="sph" @tap="showModel">
													<text>{{formdata.sph_right?formdata.sph_right:'右眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
										<view class="unit-item mgL10">
											<view style="width: 100%;">
												<view :class="formdata.sph_left?'':'hui'" class="flex-e" data-field="sph_left" data-type="sph" @tap="showModel">
													<text>{{formdata.sph_left?formdata.sph_left:'左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">柱镜(CYL)</view>
								<view class="form-value">
									<view class="dushu">
										<view class="unit-item">
											<view style="width: 100%;">
												<view :class="formdata.cyl_right?'':'hui'" class="flex-e" data-field="cyl_right" data-type="cyl" @tap="showModel">
													<text>{{formdata.cyl_right?formdata.cyl_right:'右眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
										<view class="unit-item mgL10">
											<view style="width: 100%;">
												<view :class="formdata.cyl_left?'':'hui'" class="flex-e" data-field="cyl_left" data-type="cyl" @tap="showModel">
													<text>{{formdata.cyl_left?formdata.cyl_left:'左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">轴位(AX)</view>
								<view class="form-value">
									<view class="dushu">
										<view class="unit-item">
											<view style="width: 100%;">
												<view :class="formdata.ax_right>-1?'':'hui'" class="flex-e" data-field="ax_right" data-type="ax" @tap="showModel">
													<text>{{formdata.ax_right>-1? formdata.ax_right : '右眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
										<view class="unit-item mgL10">
											<view style="width: 100%;">
												<view :class="formdata.ax_left>-1?'':'hui'" class="flex-e" data-field="ax_left" data-type="ax" @tap="showModel">
													<text>{{formdata.ax_left>-1? formdata.ax_left : '左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">下加光(ADD)</view>
								<view class="form-value">
									<view class="dushu">
										<view class="unit-item">
											<view style="width: 100%;">
												<view :class="formdata.add_right?'':'hui'" class="flex-e" data-field="add_right" data-type="add" @tap="showModel">
													<text>{{formdata.add_right?formdata.add_right:'右眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
										<view class="unit-item mgL10">
											<view style="width: 100%;">
												<view :class="formdata.add_left?'':'hui'" class="flex-e" data-field="add_left" data-type="add" @tap="showModel">
													<text>{{formdata.add_left?formdata.add_left:'左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">数量(QTY)</view>
								<view class="form-value">
									<view class="dushu">
										<view class="unit-item">
											<view style="width: 100%;">
												<input type="number" name="qty_right" placeholder="右眼" v-model="detail.qty_right" placeholder-style="font-size:24rpx;color:#cccccc" />
											</view>
										</view>
										<view class="unit-item mgL10">
											<view style="width: 100%;">
												<input type="number" name="qty_left" placeholder="左眼" v-model="detail.qty_left"  placeholder-style="font-size:24rpx;color:#cccccc" />
											</view>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="box">
						<view class="form-item textarea-item">
							<view class="form-label" style="width: 120rpx;">备注</view>
							<view class="form-value">
								<textarea class="textarea"  name="remark" :value="detail.remark" placeholder="请输入备注信息" placeholder-style="font-size:28rpx;color:#cccccc" ></textarea>
							</view>
						</view>
					</view>
					<view class="box">
						<view class="tips-info flex-bt show-box" @click="toggleBoxContent">
					    <view>扩展信息</view>
					    <view>
					      <text :class="iconClass" style="color:#999;font-weight:normal"></text>
					    </view>
					  </view>
						<view class="box-content" :style="{ height: boxContentHeight }">
					    <view class="table-body">
								
					      <view class="form-item">
					      	<view class="form-label">
					      		<view>瞳距(PD)</view>
					      		<view style="font-size: 24rpx;">双瞳距<checkbox style="transform: scale(0.6);" @click="pdchange" :checked="doublepd"></checkbox></view>
					      	</view>
					      	<view class="form-value">
					      		<view class="flex-s" v-if="doublepd">
					      			<view class="unit-item">
					      				<picker style="width: 100%;" name="ipd_right" :value="ipd_right_index" mode="selector" :range="ipdlist"  @change="selectChange" data-field="ipd_right">
					      						<view :class="ipd_right_index>-1?'':'hui'" class="flex-e">
					      							<text>{{ipd_right_index>-1?ipdlist[ipd_right_index]:'右眼'}}</text>
					      							<image class="down" :src="pre_url+'/static/img/arrowright.png'">
					      						</view>
					      				</picker>
					      			</view>
					      			<view class="unit-item">
					      				<picker style="width: 100%;" name="ipd_left" :value="ipd_left_index" mode="selector" :range="ipdlist"  @change="selectChange" data-field="ipd_left">
					      						<view :class="ipd_left_index>-1?'':'hui'" class="flex-e">
					      							<text>{{ipd_left_index>-1?ipdlist[ipd_left_index]:'左眼'}}</text>
					      							<image class="down" :src="pre_url+'/static/img/arrowright.png'">
					      						</view>
					      				</picker>
					      			</view>
					      		</view>
					      		<view class="unit-item" v-if="!doublepd">
					      			<picker style="width: 100%;" name="pd" :value="pd_index" mode="selector" :range="pdlist"  @change="selectChange" data-field="pd">
					      					<view :class="pd_index>-1?'':'hui'" class="flex-e">
					      						<text>{{pd_index>-1?pdlist[pd_index]:'瞳距'}}</text>
					      						<image class="down" :src="pre_url+'/static/img/arrowright.png'">
					      					</view>
					      			</picker>
					      		</view>
					      	</view>
					      </view>
								<view class="form-item" v-if="formdata.add_right != 0 && formdata.add_left != 0">
									<view class="form-label">
										<view>近瞳距(NPD)</view>
										<view style="font-size: 24rpx;">双瞳距<checkbox style="transform: scale(0.6);" @click="npdchange" :checked="doublenpd"></checkbox></view>
									</view>
									<view class="form-value">
										<view class="flex-s" v-if="doublenpd">
											<view class="unit-item">
												<picker style="width: 100%;" name="npd_right" :value="npd_right_index" mode="selector" :range="npdlist"  @change="selectChange" data-field="npd_right">
														<view :class="npd_right_index>-1?'':'hui'" class="flex-e">
															<text>{{npd_right_index>-1?npdlist[npd_right_index]:'右眼'}}</text>
															<image class="down" :src="pre_url+'/static/img/arrowright.png'">
														</view>
												</picker>
											</view>
											<view class="unit-item">
												<picker style="width: 100%;" name="npd_left" :value="npd_left_index" mode="selector" :range="npdlist"  @change="selectChange" data-field="npd_left">
														<view :class="npd_left_index>-1?'':'hui'" class="flex-e">
															<text>{{npd_left_index>-1?npdlist[npd_left_index]:'左眼'}}</text>
															<image class="down" :src="pre_url+'/static/img/arrowright.png'">
														</view>
												</picker>
											</view>
										</view>
										<view class="unit-item" v-if="!doublenpd">
											<picker style="width: 100%;" name="npd" :value="npd_index" mode="selector" :range="npdlist"  @change="selectChange" data-field="npd">
													<view :class="npd_index>-1?'':'hui'" class="flex-e">
														<text>{{npd_index>-1?npdlist[npd_index]:'瞳距'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
											</picker>
										</view>
									</view>
								</view>
								
								<view class="form-item">
									<view class="form-label">瞳高</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="seg_right" placeholder="1" v-model="detail.seg_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="seg_left" placeholder="1" v-model="detail.seg_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item" v-if="formdata.add_right != 0 && formdata.add_left != 0">
									<view class="form-label">通道</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
											<picker style="width: 100%;" name="corrlen_right" :value="corrlen_right_index" mode="selector" :range="corrlenlist"  @change="selectChange" data-field="corrlen_right">
													<view :class="corrlen_right_index>-1?'':'hui'" class="flex-e">
														<text>{{corrlen_right_index>-1?corrlenlist[corrlen_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
											</picker>
											</view>
											<view class="unit-item mgL10">
											<picker style="width: 100%;" name="corrlen_left" :value="corrlen_left_index" mode="selector" :range="corrlenlist"  @change="selectChange" data-field="corrlen_left">
												<view :class="corrlen_left_index>-1?'':'hui'" class="flex-e">
													<text>{{corrlen_left_index>-1?corrlenlist[corrlen_left_index]:'左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
											</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜框型号</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="frame_number_right" placeholder="右眼" v-model="detail.frame_number_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="frame_number_left" placeholder="左眼" v-model="detail.frame_number_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">厂商</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="frame_firm_right" placeholder="右眼" v-model="detail.frame_firm_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="frame_firm_left" placeholder="左眼" v-model="detail.frame_firm_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">颜色</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="frame_color_right" placeholder="右眼" v-model="detail.frame_color_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="frame_color_left" placeholder="左眼" v-model="detail.frame_color_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜框类型</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="frame_type_right" :value="frame_type_right_index" mode="selector" :range="frametypelist"  @change="selectChange" data-field="frame_type_right">
													<view :class="frame_type_right_index>-1?'':'hui'" class="flex-e">
														<text>{{frame_type_right_index>-1?frametypelist[frame_type_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="frame_type_left" :value="frame_type_left_index" mode="selector" :range="frametypelist"  @change="selectChange" data-field="frame_type_left">
													<view :class="frame_type_left_index>-1?'':'hui'" class="flex-e">
														<text>{{frame_type_left_index>-1?frametypelist[frame_type_left_index]:'左眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜高</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="hbox_right" placeholder="右眼" v-model="detail.hbox_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="hbox_left" placeholder="左眼" v-model="detail.hbox_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜宽</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="vbox_right" placeholder="右眼" v-model="detail.vbox_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="vbox_left" placeholder="左眼" v-model="detail.vbox_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">鼻梁距</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="dbl_right" placeholder="右眼" v-model="detail.dbl_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="dbl_left" placeholder="左眼" v-model="detail.dbl_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">有效直径</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="fed_right" placeholder="右眼" v-model="detail.fed_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="fed_left" placeholder="左眼" v-model="detail.fed_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">眼镜总宽</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="fwd_right" placeholder="右眼" v-model="detail.fwd_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="fwd_left" placeholder="左眼" v-model="detail.fwd_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">前倾角</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="panto_right" placeholder="右眼" v-model="detail.panto_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="panto_left" placeholder="左眼" v-model="detail.panto_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜面倾斜角</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="ztilt_right" placeholder="右眼" v-model="detail.ztilt_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="ztilt_left" placeholder="左眼" v-model="detail.ztilt_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜眼距</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="bvd_right" placeholder="右眼" v-model="detail.bvd_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="bvd_left" placeholder="左眼" v-model="detail.bvd_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
					    </view>
							<view class="form-item table-header">
								<checkbox style="transform: scale(0.6);"  @click="addlingjing" :checked="is_lingjing"></checkbox>
								<view class="form-label" style="width:100%">棱镜信息（加价20元）</view>
							</view>
							<view class="table-body">
								<view class="form-item">
									<view class="form-label">水平棱镜</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="prvm_x_right" :value="prvm_x_right_index" mode="selector" :range="prvmlist"  @change="selectChange" data-field="prvm_x_right">
													<view :class="prvm_x_right_index>-1?'':'hui'" class="flex-e">
														<text>{{prvm_x_right_index>-1?prvmlist[prvm_x_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="prvm_x_left" :value="prvm_x_left_index" mode="selector" :range="prvmlist"  @change="selectChange" data-field="prvm_x_left">
													<view :class="prvm_x_left_index>-1?'':'hui'" class="flex-e">
														<text>{{prvm_x_left_index>-1?prvmlist[prvm_x_left_index]:'左眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">水平底向</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="prva_x_right" :value="prva_x_right_index" mode="selector" :range="prvaxlist"  @change="selectChange" data-field="prva_x_right">
													<view :class="prva_x_right_index>-1?'':'hui'" class="flex-e">
														<text>{{prva_x_right_index>-1?prvaxlist[prva_x_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="prva_x_left" :value="prva_x_left_index" mode="selector" :range="prvaxlist"  @change="selectChange" data-field="prva_x_left">
													<view :class="prva_x_left_index>-1?'':'hui'" class="flex-e">
														<text>{{prva_x_left_index>-1?prvaxlist[prva_x_left_index]:'左眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">垂直棱镜</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="prvm_y_right" :value="prvm_y_right_index" mode="selector" :range="prvmlist"  @change="selectChange" data-field="prvm_y_right">
													<view :class="prvm_y_right_index>-1?'':'hui'" class="flex-e">
														<text>{{prvm_y_right_index>-1?prvmlist[prvm_y_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="prvm_y_left" :value="prvm_y_left_index" mode="selector" :range="prvmlist"  @change="selectChange" data-field="prvm_y_left">
													<view :class="prvm_y_left_index>-1?'':'hui'" class="flex-e">
														<text>{{prvm_y_left_index>-1?prvmlist[prvm_y_left_index]:'左眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">垂直底向</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="prva_y_right" :value="prva_y_right_index" mode="selector" :range="prvaylist"  @change="selectChange" data-field="prva_y_right">
													<view :class="prva_y_right_index>-1?'':'hui'" class="flex-e">
														<text>{{prva_y_right_index>-1?prvaylist[prva_y_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="prva_y_left" :value="prva_y_left_index" mode="selector" :range="prvaylist"  @change="selectChange" data-field="prva_y_left">
													<view :class="prva_y_left_index>-1?'':'hui'" class="flex-e">
														<text>{{prva_y_left_index>-1?prvaylist[prva_y_left_index]:'左眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
							</view>
					  
							<view class="form-item table-header">
								<view class="form-label" style="width:100%">镜片工艺</view>
							</view>
							<view class="table-body">
								<view class="form-item">
									<view class="form-label">镀膜</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="fcoat_right" :value="fcoat_right_index" mode="selector" :range="fcoatlist"  @change="selectChange" data-field="fcoat_right">
													<view :class="fcoat_right_index>-1?'':'hui'" class="flex-e">
														<text>{{fcoat_right_index>-1?fcoatlist[fcoat_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="fcoat_left" :value="fcoat_left_index" mode="selector" :range="fcoatlist"  @change="selectChange" data-field="fcoat_left">
													<view :class="fcoat_left_index>-1?'':'hui'" class="flex-e">
														<text>{{fcoat_left_index>-1?fcoatlist[fcoat_left_index]:'左眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">染色</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<picker style="width: 100%;" name="tint_right" :value="tint_right_index" mode="selector" :range="tintlist"  @change="selectChange" data-field="tint_right">
													<view :class="tint_right_index>-1?'':'hui'" class="flex-e">
														<text>{{tint_right_index>-1?tintlist[tint_right_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
											<view class="unit-item mgL10">
												<picker style="width: 100%;" name="tint_left" :value="tint_left_index" mode="selector" :range="tintlist"  @change="selectChange" data-field="tint_left">
													<view :class="tint_left_index>-1?'':'hui'" class="flex-e">
														<text>{{tint_left_index>-1?tintlist[tint_left_index]:'右眼'}}</text>
														<image class="down" :src="pre_url+'/static/img/arrowright.png'">
													</view>
												</picker>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">颜色</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="colr_right" placeholder="右眼" v-model="detail.colr_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="colr_left" placeholder="左眼" v-model="detail.colr_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜片直径</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="crib_right" placeholder="右眼" v-model="detail.crib_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="crib_left" placeholder="左眼" v-model="detail.crib_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜片边缘厚度</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="minedg_right" placeholder="右眼" v-model="detail.minedg_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="minedg_left" placeholder="左眼" v-model="detail.minedg_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜片中心厚度</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="minctr_right" placeholder="右眼" v-model="detail.minctr_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="minctr_left" placeholder="左眼" v-model="detail.minctr_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">镜片基弯</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="mbase_right" placeholder="右眼" v-model="detail.mbase_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="mbase_left" placeholder="左眼" v-model="detail.mbase_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">标记</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="inkmask_right" placeholder="右眼" v-model="detail.inkmask_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="inkmask_left" placeholder="左眼" v-model="detail.inkmask_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">BCERIN</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="bcerin_right" placeholder="右眼" v-model="detail.bcerin_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="bcerin_left" placeholder="左眼" v-model="detail.bcerin_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-label">BCERUP</view>
									<view class="form-value">
										<view class="dushu">
											<view class="unit-item">
												<view style="width: 100%;">
													<input type="text" name="bcerup_right" placeholder="右眼" v-model="detail.bcerup_right" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
											<view class="unit-item mgL10">
												<view style="width: 100%;">
													<input type="text" name="bcerup_left" placeholder="左眼" v-model="detail.bcerup_left" placeholder-style="font-size:24rpx;color:#cccccc" />
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>	
						</view>
					</view>
					
					<view class="bottom">
						<view class="flex-x-center">
							<radio-group @click="ruleChange">
								<label>
									<radio style="transform: scale(0.8);" :color="t('color1')" :checked="isrule" />
								</label>
							</radio-group>
							<view>我已阅读并同意<text class="red" @tap="goto" data-url="/pagesExt/glass/set?field=xieyi">《用户信息协议授权协议》</text>中的全部内容</view>
						</view>
						<view class="form-option">
							<button class="btn"
								:style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}"
								form-type="submit" data-type="1">保存</button>
						</view>
					</view>
				</form>
			</view>
			<!-- 弹窗 -->
			<uni-drawer ref="showModel" mode="right" :width="280">
				<scroll-view scroll-y="true" class="filter-scroll-view filter-page">
					<view class="filter-scroll-view-box">
						<view class="search-filter" style="padding-bottom: 150rpx;">
							<block v-if="popuptype == 'sph'">
								<view class="filter-content-title">定制球镜</view>
								<view class="search-filter-content">
									<block v-for="(sph,sph_index) in sphlist" :key="sph_index">
										<view class="filter-item" @tap.stop="drawerChange(sph)" :style="sph == formdata[popupfield] ? 'background:linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%);color:#ffffff' : ''">{{sph}}</view>
									</block>
								</view>
							</block>
							<block v-if="popuptype == 'cyl'">
								<view class="filter-content-title">定制柱镜</view>
								<view class="search-filter-content">
									<block v-for="(cyl,cyl_index) in cyllist" :key="cyl_index">
										<view class="filter-item" @tap.stop="drawerChange(cyl)" :style="cyl == formdata[popupfield] ? 'background:linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%);color:#ffffff' : ''">{{cyl}}</view>
									</block>
								</view>
							</block>
							<block v-if="popuptype == 'ax'">
								<view class="filter-content-title">轴位</view>
								<view class="search-filter-content">
									<block v-for="(ax,ax_index) in axlist" :key="ax_index">
										<view class="filter-item" @tap.stop="drawerChange(ax)" :style="ax == formdata[popupfield] ? 'background:linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%);color:#ffffff' : ''">{{ax}}</view>
									</block>
								</view>
							</block>
							<block v-if="popuptype == 'add'">
								<view class="filter-content-title">下加光</view>
								<view class="search-filter-content">
									<block v-for="(add,add_index) in addlist" :key="add_index">
										<view class="filter-item" @tap.stop="drawerChange(add)" :style="add == formdata[popupfield] ? 'background:linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%);color:#ffffff' : ''">{{add}}</view>
									</block>
								</view>
							</block>
						</view>
					</view>
				</scroll-view>
			</uni-drawer>
		</block>
    <view style="display: none">{{txt}}</view>
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,

				pre_url: app.globalData.pre_url,
				type: 1,
				detail: {},
				isrule: false,
				isAts: 0,
				id: 0,
				set: {},
				visionlist: [],
				doublepd: false, //是否开启双瞳距-远瞳距
				doublenpd: false, //是否开启双瞳距-近瞳距
				
				sphlist: [], //球镜 -30 15 0.25
				cyllist: [], //柱镜 -0.8 8.00 0.25
				axlist: [],  //轴位 0 180 1
				addlist: [], //下加光 0 4 0.25
				ipdlist:[],  //瞳距 15 40 0.5
				pdlist:[],   //双瞳距 45 80 1
				npdlist:[],  //双瞳距 15 40 0.5
				corrlenlist:[],//镜片通道 9 20 1
				prvmlist:[],   //水平、垂直棱镜 0.50 5 0.5 
				frametypelist:['全框','半款','无框'], //镜框类型
				prvaxlist:['n/a','IN','OUT'], //水平底向
				prvaylist:['n/a','UP','Down'],//垂直底向
				fcoatlist:['基片','单加硬','绿膜','蓝膜','超发水绿膜','防眩光膜','A4膜','X6膜','内镀绿膜','内镀蓝膜','底面旋涂加硬'], //镀膜
				tintlist:['无色','全色','渐变色'], //染色
				
				ipd_right_index:-1,
				ipd_left_index:-1,
				pd_index:-1,
				npd_right_index:-1,
				npd_left_index:-1,
				npd_index:-1,
				frame_type_right_index:-1,
				frame_type_left_index:-1,
				prvm_x_right_index:-1,
				prvm_x_left_index:-1,
				prvm_y_right_index:-1,
				prvm_y_left_index:-1,
				prva_x_right_index:-1,
				prva_x_left_index:-1,
				prva_y_right_index:-1,
				prva_y_left_index:-1,
				corrlen_right_index:-1,
				corrlen_left_index:-1,
				fcoat_right_index:-1,
				fcoat_left_index:-1,
				tint_right_index:-1,
				tint_left_index:-1,
				
				isShowModal: false,
				formdata: {},
				popuptype: '',
				popupfield: '',
				is_lingjing:0,//是否添加棱镜 加价20
				
				boxContentHeight: 'auto', // 客户信息 初始高度为0 默认隐藏
				iconClass: 'iconfont icondaoxu', // 初始图标类名
				txt:''
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || 0;
			this.getdata();
		},
		onPullDownRefresh: function() {},
		onReachBottom: function() {},
		methods: {
			getdata: function(loadmore) {
				var that = this;
				that.loading = true;
				app.post('ApiGlassCustom/add', {
					id: that.id
				}, function(res) {
					that.loading = false;
					that.set = res.set;
					that.initlist('sph', -30, 15, 0.25, 2)
					that.initlist('cyl', -0.8, 8, 0.25, 2)
					that.initlist('ax', 0, 180, 1, 0)
					that.initlist('add', 0, 4.00, 0.25, 2)
					that.initlist('ipd',15,40,0.5,2)
					that.initlist('npd',15,40,0.5,2)
					that.initlist('pd',45,80,0.5,2)
					that.initlist('corrlen',9,20,1,0)
					that.initlist('prvm',0.5,5,0.5,2)
					
					that.detail = res.detail
					if (that.set && that.set.vision) {
						that.visionlist = that.set.vision
					}
					if (that.detail.id) {
						that.isrule = true
						
						that.formdata.sph_right = that.detail.sph_right;
						that.formdata.sph_left = that.detail.sph_left;
						that.formdata.cyl_right = that.detail.cyl_right;
						that.formdata.cyl_left = that.detail.cyl_left;
						that.formdata.ax_right = that.detail.ax_right;
						that.formdata.ax_left = that.detail.ax_left;
						that.formdata.add_right = that.detail.add_right;
						that.formdata.add_left = that.detail.add_left;
						that.is_lingjing = that.detail.is_lingjing;
						// 瞳距
						that.doublepd = that.detail.double_ipd ? true : false;
						if(that.detail.double_ipd){
							if(that.detail.ipd_right || that.detail.ipd_left){
								for(let i in that.ipdlist){
									 let vision = that.ipdlist[i];
									 if(vision==that.detail.ipd_right){
										 that.ipd_right_index = i;
									 }
									 if(vision==that.detail.ipd_left){
											that.ipd_left_index = i;
									 }
								}
							}
						}else{
							for(let i in that.pdlist){
								 let vision = that.pdlist[i];
								 var pd = that.detail.pd
								 if(vision==pd){
									 that.pd_index = i;
								 }
							}
						}
						// 近瞳距
						that.doublenpd = that.detail.double_npd ? true : false;
						if(that.detail.double_npd){
							if(that.detail.npd_right || that.detail.npd_left){
								for(let i in that.npdlist){
									 let vision = that.npdlist[i];
									 if(vision==that.detail.npd_right){
										 that.npd_right_index = i;
									 }
									 if(vision==that.detail.npd_left){
											that.npd_left_index = i;
									 }
								}
							}
						}else{
							for(let i in that.npdlist){
								 let vision = that.npdlist[i];
								 var npd = that.detail.npd
								 if(vision==npd){
									 that.npd_index = i;
								 }
							}
						}
						// 通道
						if(that.detail.corrlen_right || that.detail.corrlen_left){
							for(let i in that.corrlenlist){
								 let vision = that.corrlenlist[i];
								 if(vision==that.detail.corrlen_right){
									 that.corrlen_right_index = i;
								 }
								 if(vision==that.detail.corrlen_left){
										that.corrlen_left_index = i;
								 }
							}
						}
						// 镜框类型
						if(that.detail.frame_type_right || that.detail.frame_type_left){
							for(let i in that.frametypelist){
								 let vision = that.frametypelist[i];
								 if(vision==that.detail.frame_type_right){
									 that.frame_type_right_index = i;
								 }
								 if(vision==that.detail.frame_type_left){
										that.frame_type_left_index = i;
								 }
							}
						}
						// 水平棱镜
						if(that.detail.prvm_x_right || that.detail.prvm_x_left){
							for(let i in that.prvmlist){
								 let vision = that.prvmlist[i];
								 if(vision==that.detail.prvm_x_right){
									 that.prvm_x_right_index = i;
								 }
								 if(vision==that.detail.prvm_x_left){
										that.prvm_x_left_index = i;
								 }
							}
						}
						// 水平底向
						if(that.detail.prva_x_right || that.detail.prva_x_left){
							for(let i in that.prvaxlist){
								 let vision = that.prvaxlist[i];
								 if(vision==that.detail.prva_x_right){
									 that.prva_x_right_index = i;
								 }
								 if(vision==that.detail.prva_x_left){
										that.prva_x_left_index = i;
								 }
							}
						}
						// 垂直棱镜
						if(that.detail.prvm_y_right || that.detail.prvm_y_left){
							for(let i in that.prvmlist){
								 let vision = that.prvmlist[i];
								 if(vision==that.detail.prvm_y_right){
									 that.prvm_y_right_index = i;
								 }
								 if(vision==that.detail.prvm_y_left){
										that.prvm_y_left_index = i;
								 }
							}
						}
						// 垂直底向
						if(that.detail.prva_y_right || that.detail.prva_y_left){
							for(let i in that.prvaylist){
								 let vision = that.prvaylist[i];
								 if(vision==that.detail.prva_y_right){
									 that.prva_y_right_index = i;
								 }
								 if(vision==that.detail.prva_y_left){
										that.prva_y_left_index = i;
								 }
							}
						}
						// 镀膜
						if(that.detail.fcoat_right || that.detail.fcoat_left){
							for(let i in that.fcoatlist){
								 let vision = that.fcoatlist[i];
								 if(vision==that.detail.fcoat_right){
									 that.fcoat_right_index = i;
								 }
								 if(vision==that.detail.fcoat_left){
										that.fcoat_left_index = i;
								 }
							}
						}
						// 染色
						if(that.detail.tint_right || that.detail.tint_left){
							for(let i in that.tintlist){
								 let vision = that.tintlist[i];
								 if(vision==that.detail.tint_right){
									 that.tint_right_index = i;
								 }
								 if(vision==that.detail.tint_left){
										that.tint_left_index = i;
								 }
							}
						}
					}
					that.txt = Math.random()
					that.loaded()
				});
			},
			initlist: function(field, min, max, step, point = 2) {
				var that = this;
				var min = parseFloat(min);
				var max = parseFloat(max);
				var step = parseFloat(step);
				var listarr = [];
				var index = 0;
				if(field=='prvm'){
					listarr.push('n/a');
				}
				for (var i = min; i <= max; i = i + step) {
					var v = '';
					if (i < 0) {
						v = i.toFixed(point);
					} else if (i == 0) {
						v = i.toFixed(point);
					} else {
						if (field == 'degress') {
							var v = '+' + (i.toFixed(point))
						} else {
							if (min < 0) {
								var v = '+' + (i.toFixed(point))
							} else {
								v = i.toFixed(point);
							}
						}
					}

					listarr.push(v)
					index++
				}
				that[field + 'list'] = listarr
			},
			pdchange: function(e) {
				this.doublepd = this.doublepd ? false : true
			},
			npdchange: function(e) {
				this.doublenpd = this.doublenpd ? false : true
			},
			ruleChange: function(e) {
				this.isrule = (this.isrule ? false : true)
			},
			formSubmit: function(e) {
				var that = this;
				var formdata = e.detail.value;
				var changedata = that.formdata;
				
				if (formdata.name == '') {
					return app.error('请输入定制参数名称');
				}
				if (!changedata.sph_right) {
					return app.error('请选择右眼球镜');
				}
				if (!changedata.sph_left) {
					 return app.error('请选择左眼球镜');
				}
				if (!changedata.cyl_right) {
					return app.error('请选择右眼柱镜');
				}
				if (!changedata.cyl_left) {
					 return app.error('请选择左眼柱镜');
				}	
				if (app.isNull(changedata.ax_right)) {
					return app.error('请选择右眼轴位');
				}
				if (app.isNull(changedata.ax_left)) {
					 return app.error('请选择左眼轴位');
				}
				if (!changedata.add_right) {
					 return app.error('请选择右眼下加光');
				}
				if (!changedata.add_left) {
					 return app.error('请选择左眼下加光');
				}
				if(formdata.qty_left <= 0 && formdata.qty_right <= 0){
					return app.error('数量不可小于0');
				}
				// 瞳距
				if(that.doublepd){
					formdata['ipd_right'] = (that.ipd_right_index > -1) ? that.ipdlist[that.ipd_right_index] : '';
					formdata['ipd_left'] = (that.ipd_left_index > -1) ? that.ipdlist[that.ipd_left_index] : '';
					formdata['double_ipd'] = 1;
				}else{
					formdata['pd'] = (that.pd_index > -1) ? that.pdlist[that.pd_index] : '';
				}
				// 近瞳距
				if(that.doublenpd){
				    formdata['npd_right'] = (that.npd_right_index > -1) ? that.npdlist[that.npd_right_index] : '';
				    formdata['npd_left'] = (that.npd_left_index > -1) ? that.npdlist[that.npd_left_index] : '';
				    formdata['double_npd'] = 1;
				}else{
				    formdata['npd'] = (that.npd_index > -1) ? that.npdlist[that.npd_index] : ''; 
				}
				// 通道
				formdata['corrlen_right'] = (that.corrlen_right_index > -1) ? that.corrlenlist[that.corrlen_right_index] : '';
				formdata['corrlen_left'] = (that.corrlen_left_index > -1) ? that.corrlenlist[that.corrlen_left_index] : '';
				// 镜框类型
				formdata['frame_type_right'] = (that.frame_type_right_index > -1) ? that.frametypelist[that.frame_type_right_index] : '';
				formdata['frame_type_left'] = (that.frame_type_left_index > -1) ? that.frametypelist[that.frame_type_left_index] : '';
				// 水平棱镜
				formdata['prvm_x_right'] = (that.prvm_x_right_index > -1) ? that.prvmlist[that.prvm_x_right_index] : '';
				formdata['prvm_x_left'] = (that.prvm_x_left_index > -1) ? that.prvmlist[that.prvm_x_left_index] : '';
				// 水平底向
				formdata['prva_x_right'] = (that.prva_x_right_index > -1) ? that.prvaxlist[that.prva_x_right_index] : '';
				formdata['prva_x_left'] = (that.prva_x_left_index > -1) ? that.prvaxlist[that.prva_x_left_index] : '';
				// 垂直棱镜
				formdata['prvm_y_right'] = (that.prvm_y_right_index > -1) ? that.prvmlist[that.prvm_y_right_index] : '';
				formdata['prvm_y_left'] = (that.prvm_y_left_index > -1) ? that.prvmlist[that.prvm_y_left_index] : '';
				// 垂直底向
				formdata['prva_y_right'] = (that.prva_y_right_index > -1) ? that.prvaylist[that.prva_y_right_index] : '';
				formdata['prva_y_left'] = (that.prva_y_left_index > -1) ? that.prvaylist[that.prva_y_left_index] : '';
				// 镀膜
				formdata['fcoat_right'] = (that.fcoat_right_index > -1) ? that.fcoatlist[that.fcoat_right_index] : '';
				formdata['fcoat_left'] = (that.fcoat_left_index > -1) ? that.fcoatlist[that.fcoat_left_index] : '';
				// 染色
				formdata['tint_right'] = (that.tint_right_index > -1) ? that.tintlist[that.tint_right_index] : '';
				formdata['tint_left'] = (that.tint_left_index > -1) ? that.tintlist[that.tint_left_index] : ''; 
				
				formdata = {
						...formdata,
						...changedata
				};
				if (!that.isrule) {
					app.error('请先阅读并同意用户信息授权协议');
					return false;
				}
				formdata['id'] = that.id;
				formdata['is_lingjing'] = that.is_lingjing;
				app.showLoading('提交中');
				app.post("ApiGlassCustom/save", formdata, function(data) {
					app.showLoading(false);
					if (data.status == 1) {
						//缓存最新的档案信息
						app.setCache('_glass_custom_id', data.id)
						app.success(data.msg);
						setTimeout(function() {
							app.goback(true)
						}, 1000);
					} else {
						app.error(data.msg);
					}
				});
			},
			selectChange: function(e) {
				var index = e.detail.value;
				var field = e.currentTarget.dataset.field;
				this[field+'_index'] = index;
			},
			drawerChange: function(value) {
				this.formdata[this.popupfield] = value;
				this.isShowModal = false;
				this.popuptype = '';
				this.popupfield = '';
				this.$refs.showModel.close();
			},
			showModel: function(e) {
				this.popuptype = e.currentTarget.dataset.type;
				this.popupfield = e.currentTarget.dataset.field;
				this.$refs.showModel.open();
			},
			toggleBoxContent() {
			  const animation = uni.createAnimation({
			    duration: 300, // 动画持续时间，单位ms
			    timingFunction: 'ease', // 动画效果
			  });
			
			  if (this.boxContentHeight === '0') {
			    // 展开
			    animation.height('auto').step();
			    this.boxContentHeight = 'auto';
			    this.iconClass = 'iconfont iconshangla';
			  } else {
			    // 收起
			    animation.height(0).step();
			    this.boxContentHeight = '0';
			    this.iconClass = 'iconfont icondaoxu';
			  }
			
			  this.animation = animation.export();
			},
			addlingjing:function(){
				this.is_lingjing = !this.is_lingjing;
				this.is_lingjing = this.is_lingjing ? 1 : 0;
				console.log(this.is_lingjing)
			},
			//左右同步
			synchronous:function(){
        var that = this;
        // 球镜
        if(that.formdata.sph_right) {
          that.formdata.sph_left = that.formdata.sph_right;
        }
        // 柱镜
        if(that.formdata.cyl_right) {
          that.formdata.cyl_left = that.formdata.cyl_right;
        }
        // 轴位
        if(that.formdata.ax_right > -1) {
          that.formdata.ax_left = that.formdata.ax_right;
        }
        // 下加光
        if(that.formdata.add_right) {
          that.formdata.add_left = that.formdata.add_right;
        }
        // 瞳距
        if(that.ipd_right_index > -1) {
          that.ipd_left_index = that.ipd_right_index;
        }
        // 同步近瞳距
        if(that.npd_right_index > -1) {
          that.npd_left_index = this.npd_right_index;
        }
        // 通道
        if(that.corrlen_right_index > -1) {
          that.corrlen_left_index = that.corrlen_right_index;
        }
        // 镜框类型
        if(that.frame_type_right_index > -1) {
          that.frame_type_left_index = that.frame_type_right_index;
        }
        // 水平棱镜
        if(that.prvm_x_right_index > -1) {
          that.prvm_x_left_index = that.prvm_x_right_index;
        }
        // 水平底向
        if(that.prva_x_right_index > -1) {
          that.prva_x_left_index = that.prva_x_right_index;
        }
        // 垂直棱镜
        if(that.prvm_y_right_index > -1) {
          that.prvm_y_left_index = that.prvm_y_right_index;
        }
        // 垂直底向
        if(that.prva_y_right_index > -1) {
          that.prva_y_left_index = that.prva_y_right_index;
        }
        // 镀膜
        if(that.fcoat_right_index > -1) {
          that.fcoat_left_index = that.fcoat_right_index;
        }
        // 染色
        if(that.tint_right_index > -1) {
          that.tint_left_index = that.tint_right_index;
        }
        let rightDetail = that.detail;
        for(let key in rightDetail){
          if(key.indexOf("_right") !== -1){
            let leftKey = key.replace("_right","_left");
            that.detail[leftKey] = rightDetail[key];
          }
        }
        that.txt = Math.random()
        // 提示同步成功
        uni.showToast({
          title: '同步成功',
          icon: 'success'
        });
			}
		}
	};
</script>
<style>
	.flex{display:flex;align-items:center}
	.flex-s{display:flex;justify-content:flex-start;align-items:center}
	.flex-e{display:flex;justify-content:flex-end;align-items:center}
	.flex-sb{display:flex;justify-content:space-between;align-items:center}
	.flex-c{display:flex;justify-content:center;align-items:center}
	.hui{color:#CCCCCC}
	.top{height:400rpx}
	.content{position:relative;top:-400rpx;padding:20rpx}
	.box{width:100%;background:#FFFFFF;color:#222222;padding:20rpx;border-radius:20rpx;margin-bottom:20rpx}
	.tips-top{width:100%;text-align:center;font-size:36rpx;color:#FFFFFF;padding:30rpx 0}
	.tips-info{font-size:16px;font-weight:bold;line-height:45px}
	.tips-info .tips{font-size:30rpx;color:#cdcdcd;font-weight:normal}
	.tips-desc{color:#9d9d9d;font-size:28rpx}
	.tips-ref{margin-right:20rpx}
	.tips-desc image{width:30rpx;height:30rpx;padding-top:8rpx}
	.tips-dushu{font-size:24rpx;color:#9d9d9d;padding-top:6rpx}
	.red{color:#ff0000}
	.bottom{margin:40rpx 0}
	.dangan .form-label{font-size:32rpx;font-weight:bold}
	.form-item{display:flex;justify-content:flex-start;align-items:center;padding:30rpx 20rpx}
	.form-value textarea{padding:10rpx 0}
	.form-label{flex-shrink:0;width:200rpx;flex-wrap:wrap;padding-right:30rpx;font-size:28rpx}
	.form-value{flex:1}
	.form-tips{color:#CCCCCC;font-size:28rpx;padding:20rpx 0}
	.form-value .down{width:28rpx;height:28rpx;vertical-align:middle;flex-shrink:0}
	.form-value.radio label{margin-right:10rpx}
	.form-value.upload{display:flex;align-items:center;flex-wrap:wrap}
	.textarea-item .textarea{border:1rpx solid #e5e5e5;max-width:480rpx;border-radius:4rpx;padding:16rpx;height:160rpx;font-size:28rpx}
	.name-item input{text-align:right}
	/deep/.input-value-border{border:none}
	/deep/.input-value{line-height:normal;padding:0}
	.form-item-row{border-bottom:1rpx solid #f6f6f6;padding:20rpx 0}
	.form-item-row .form-label,.form-item-row .form-value{width:100%}
	.form-item-row .form-value textarea{width:100%;height:200rpx}
	.form-option{display:flex;justify-content:center;padding:30rpx}
	.form-option .btn{text-align:center;width:100%;border-radius:80rpx;line-height:84rpx}
	.dushu{display:flex;justify-content:space-between}
	.unit-item{display:flex;justify-content:space-between;margin:0 6rpx;border-bottom:1rpx solid #ededed;flex:1;font-size:28rpx}
	.unit-item .unit{color:#8d8d8d;line-height:44rpx;padding-left:10rpx}
	.bb{border-bottom:1rpx solid #e5e5e5}
	.mgL10{margin-left:30rpx}
	.tips-jz{width:40rpx;height:40rpx}
	.jz-item .unit-item{width:45%}
	.table-header{font-weight:bold}
	.table-header .form-value{display:flex;justify-content:flex-end;align-items:center;text-align:right}
	.table-header .table-item{width:50%;flex-shrink:0;text-align:center}
	.table-body{color:#cdcdcd;font-size:24rpx}
	.table-body .form-item{padding:20rpx 20rpx;color:#8d8d8d}
	.table-body .form-value{text-align:right;font-size:24rpx}
	.table-body .form-item .uni-input-input{font-size:24rpx}
	.box-content{overflow:hidden;height:0;transition:height 0.3s ease}
	
	.filter-page{height: 100%;}
	.filter-scroll-view{margin-top:var(--window-top)}
	.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
	.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
	.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:60rpx 0 30rpx 20rpx;}
	.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
	.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
	.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
</style>